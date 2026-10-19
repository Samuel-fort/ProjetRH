<?php

namespace App\Services\Contrat;

use App\Enums\StatutContrat;
use App\Enums\TypeContrat;
use App\Enums\TypeDocument;
use App\Exceptions\Contrat\RenouvellementImpossible;
use App\Exceptions\Contrat\TransitionContratInvalide;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Document;
use App\Models\Embauche\Employe;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ContratService
{
    private const array CHAMPS_CONTRAT = [
        'type',
        'date_debut',
        'date_fin',
        'salaire',
        'horaires',
        'lieu_travail',
    ];

    public function __construct(
        private readonly MatriculeService $matriculeService,
        private readonly AffectationService $affectationService,
        private readonly OnboardingService $onboardingService,
        private readonly DocumentService $documentService
    ) {}

    public function creer(Employe $employe, array $donnees): Contrat
    {
        $validees = $this->validerDonnees($donnees);

        return Contrat::query()->create([
            ...$validees,
            'employe_id' => $employe->getKey(),
            'statut' => StatutContrat::Cree,
            'proposition_embauche_id' => $donnees['proposition_embauche_id'] ?? null,
        ]);
    }

    public function creerDepuisProposition(Employe $employe, array $donnees): Contrat
    {
        $meta = Validator::make($donnees, [
            'proposition_embauche_id' => ['required', 'integer', 'min:1'],
            'poste_id' => ['required', 'integer', 'min:1'],
            'departement_id' => ['required', 'integer', 'min:1'],
        ])->validate();

        try {
            return DB::transaction(function () use ($employe, $donnees, $meta): Contrat {
                $existant = Contrat::query()
                    ->where('proposition_embauche_id', $meta['proposition_embauche_id'])
                    ->first();

                if ($existant !== null) {
                    return $existant;
                }

                $contrat = $this->creer($employe, [
                    'type' => $donnees['type'] ?? TypeContrat::Cdi,
                    'date_debut' => $donnees['date_debut'] ?? null,
                    'date_fin' => $donnees['date_fin'] ?? null,
                    'salaire' => $donnees['salaire'] ?? null,
                    'horaires' => $donnees['horaires'] ?? null,
                    'lieu_travail' => $donnees['lieu_travail'] ?? null,
                    'proposition_embauche_id' => $meta['proposition_embauche_id'],
                ]);

                $this->affectationService->affecter(
                    $employe,
                    (int) $meta['poste_id'],
                    (int) $meta['departement_id'],
                    CarbonImmutable::parse($contrat->date_debut)
                );

                DB::afterCommit(fn () => $this->declencherGenerationPdf($contrat));

                return $contrat;
            });
        } catch (QueryException $exception) {
            $existant = Contrat::query()
                ->where('proposition_embauche_id', $meta['proposition_embauche_id'])
                ->first();

            if ($existant !== null) {
                return $existant;
            }

            throw $exception;
        }
    }

    public function signer(Contrat $contrat, ?CarbonInterface $dateSignature = null): Contrat
    {
        return DB::transaction(function () use ($contrat, $dateSignature): Contrat {
            $contratVerrouille = Contrat::query()
                ->whereKey($contrat->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $contratVerrouille->statut->peutPasserA(StatutContrat::Signe)) {
                throw new TransitionContratInvalide('Seul un contrat créé peut être signé.');
            }

            $date = CarbonImmutable::parse($dateSignature ?? CarbonImmutable::today());
            $contratVerrouille->date_signature = $date->toDateString();
            $contratVerrouille->statut = StatutContrat::Signe;
            $this->matriculeService->attribuer($contratVerrouille->employe, $date);
            $contratVerrouille->save();

            return $contratVerrouille;
        });
    }

    public function valider(Contrat $contrat): Contrat
    {
        return DB::transaction(function () use ($contrat): Contrat {
            $contratVerrouille = Contrat::query()
                ->whereKey($contrat->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $contratVerrouille->statut->peutPasserA(StatutContrat::Valide)) {
                throw new TransitionContratInvalide('Seul un contrat signé peut être validé.');
            }

            $contratVerrouille->date_validation = CarbonImmutable::today()->toDateString();
            $contratVerrouille->statut = StatutContrat::Valide;
            $contratVerrouille->save();
            $this->onboardingService->creerPourContrat($contratVerrouille);

            return $contratVerrouille;
        });
    }

    public function renouveler(Contrat $contrat, array $donnees): Contrat
    {
        try {
            return DB::transaction(function () use ($contrat, $donnees): Contrat {
                $source = Contrat::query()
                    ->whereKey($contrat->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $source->estRenouvelable()) {
                    throw new RenouvellementImpossible;
                }

                $donneesNouveauContrat = [
                    'type' => TypeContrat::Renouvellement,
                    'date_debut' => $donnees['date_debut'] ?? null,
                    'date_fin' => $donnees['date_fin'] ?? null,
                    'salaire' => $source->salaire,
                    'horaires' => $source->horaires,
                    'lieu_travail' => $source->lieu_travail,
                    ...array_intersect_key($donnees, array_flip(self::CHAMPS_CONTRAT)),
                ];

                $validees = $this->validerDonnees($donneesNouveauContrat);

                if (CarbonImmutable::parse($validees['date_debut'])
                    ->lessThanOrEqualTo(CarbonImmutable::parse($source->date_fin))) {
                    throw ValidationException::withMessages([
                        'date_debut' => 'La date de début du nouveau contrat doit être postérieure à la date de fin du contrat précédent.',
                    ]);
                }

                return Contrat::query()->create([
                    ...$validees,
                    'employe_id' => $source->employe_id,
                    'statut' => StatutContrat::Cree,
                    'contrat_precedent_id' => $source->getKey(),
                ]);
            });
        } catch (QueryException $exception) {
            if (Contrat::query()->whereKey($contrat->getKey())->first()?->suivant()->exists()) {
                throw new RenouvellementImpossible;
            }

            throw $exception;
        }
    }

    public function modifier(Contrat $contrat, array $donnees): Contrat
    {
        return DB::transaction(function () use ($contrat, $donnees): Contrat {
            $contratVerrouille = Contrat::query()
                ->whereKey($contrat->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $contratVerrouille->estModifiable()) {
                throw new TransitionContratInvalide('Un contrat signé ou validé ne peut plus être modifié.');
            }

            $donneesCompletes = array_merge(
                $contratVerrouille->only(self::CHAMPS_CONTRAT),
                array_intersect_key($donnees, array_flip(self::CHAMPS_CONTRAT))
            );

            $contratVerrouille->fill($this->validerDonnees($donneesCompletes));
            $contratVerrouille->save();

            return $contratVerrouille;
        });
    }

    public function declencherGenerationPdf(Contrat $contrat): void
    {
        $classeGenerateur = 'App\\Services\\Embauche\\GenererContratPdf';

        if (! class_exists($classeGenerateur)) {
            return;
        }

        try {
            $resultat = app($classeGenerateur)->generer($contrat);

            if (is_string($resultat) && $resultat !== '') {
                $this->documentService->enregistrerChemin(
                    $contrat,
                    $resultat,
                    TypeDocument::ContratGenere
                );
            } elseif (! $resultat instanceof Document && $resultat !== null) {
                Log::warning('Le générateur PDF a retourné un résultat non pris en charge.', [
                    'contrat_id' => $contrat->getKey(),
                    'type_retour' => get_debug_type($resultat),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('La génération du PDF du contrat a échoué.', [
                'contrat_id' => $contrat->getKey(),
                'exception' => $exception,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validerDonnees(array $donnees): array
    {
        $type = $donnees['type'] ?? null;
        if ($type instanceof TypeContrat) {
            $type = $type->value;
        }

        $validateur = Validator::make($donnees, [
            'type' => ['required', Rule::enum(TypeContrat::class)],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date'],
            'salaire' => ['required', 'numeric', 'min:0'],
            'horaires' => ['required', 'string'],
            'lieu_travail' => ['required', 'string'],
            'proposition_embauche_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $validateur->after(function ($validateur) use ($donnees, $type): void {
            if ($validateur->errors()->isNotEmpty()) {
                return;
            }

            $typeContrat = TypeContrat::tryFrom((string) $type);
            if ($typeContrat === null) {
                return;
            }

            $dateFin = $donnees['date_fin'] ?? null;
            if ($typeContrat->aDureeDeterminee() && ($dateFin === null || $dateFin === '')) {
                $validateur->errors()->add('date_fin', 'La date de fin est obligatoire pour ce type de contrat.');
            } elseif (! $typeContrat->aDureeDeterminee() && $dateFin !== null && $dateFin !== '') {
                $validateur->errors()->add('date_fin', 'La date de fin doit être nulle pour un CDI.');
            } elseif ($dateFin !== null && $dateFin !== ''
                && CarbonImmutable::parse($dateFin)->lessThanOrEqualTo(CarbonImmutable::parse($donnees['date_debut']))) {
                $validateur->errors()->add('date_fin', 'La date de fin doit être postérieure à la date de début.');
            }

            foreach (['horaires', 'lieu_travail'] as $champ) {
                if (isset($donnees[$champ]) && trim((string) $donnees[$champ]) === '') {
                    $validateur->errors()->add($champ, 'Ce champ ne peut pas être vide.');
                }
            }
        });

        return $validateur->validate();
    }
}
