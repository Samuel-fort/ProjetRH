<?php

namespace Database\Seeders;

use App\Enums\StatutContrat;
use App\Enums\StatutTache;
use App\Enums\TypeContrat;
use App\Enums\TypeDocument;
use App\Enums\TypeTacheOnboarding;
use App\Models\Besoin\Departement;
use App\Models\Besoin\Poste;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\ContratHistorique;
use App\Models\Contrat\Onboarding;
use App\Models\Contrat\OnboardingTache;
use App\Models\Embauche\Employe;
use App\Models\User;
use App\Services\Contrat\AffectationService;
use App\Services\Contrat\ContratService;
use App\Services\Contrat\DocumentService;
use App\Services\Contrat\OnboardingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContratSeeder extends Seeder
{
    public function run(): void
    {
        if (Contrat::query()->exists()) {
            return;
        }

        Model::withoutEvents(function (): void {
            DB::transaction(function (): void {
                $services = [
                    'contrats' => app(ContratService::class),
                    'affectations' => app(AffectationService::class),
                    'onboardings' => app(OnboardingService::class),
                    'documents' => app(DocumentService::class),
                ];
                $employes = $this->employesDeDemonstration();
                [$departementPrincipal, $departementSecondaire] = $this->departementsDeDemonstration();
                $postePrincipal = $this->postePour($departementPrincipal, 'Chargé de recrutement');
                $posteSecondaire = $this->postePour($departementSecondaire, 'Gestionnaire administratif');
                $aujourdHui = CarbonImmutable::today();

                $contratCree = $services['contrats']->creer($employes[0], [
                    'type' => TypeContrat::Cdd,
                    'date_debut' => $aujourdHui->subDays(15),
                    'date_fin' => $aujourdHui->addMonths(12),
                    'salaire' => 2800,
                    'horaires' => '08h00 - 17h00',
                    'lieu_travail' => 'Paris',
                ]);
                $this->enregistrerHistorique($contratCree, null, StatutContrat::Cree);
                $services['affectations']->affecter(
                    $employes[0],
                    $postePrincipal->getKey(),
                    $departementPrincipal->getKey(),
                    $aujourdHui->subDays(15)
                );

                $contratSigne = $services['contrats']->creer($employes[1], [
                    'type' => TypeContrat::Cdi,
                    'date_debut' => $aujourdHui->subDays(7),
                    'date_fin' => null,
                    'salaire' => 3400,
                    'horaires' => '09h00 - 17h30',
                    'lieu_travail' => 'Lyon',
                ]);
                $this->enregistrerHistorique($contratSigne, null, StatutContrat::Cree);
                $services['contrats']->signer($contratSigne, $aujourdHui->subDays(7));
                $this->enregistrerHistorique($contratSigne, StatutContrat::Cree, StatutContrat::Signe);

                $contratCdiValide = $services['contrats']->creer($employes[2], [
                    'type' => TypeContrat::Cdi,
                    'date_debut' => $aujourdHui->subDays(45),
                    'date_fin' => null,
                    'salaire' => 3900,
                    'horaires' => '08h30 - 17h00',
                    'lieu_travail' => 'Bordeaux',
                ]);
                $this->enregistrerHistorique($contratCdiValide, null, StatutContrat::Cree);
                $services['contrats']->signer($contratCdiValide, $aujourdHui->subDays(45));
                $this->enregistrerHistorique($contratCdiValide, StatutContrat::Cree, StatutContrat::Signe);
                $services['contrats']->valider($contratCdiValide);
                $this->enregistrerHistorique($contratCdiValide, StatutContrat::Signe, StatutContrat::Valide);

                $onboardingCdi = Onboarding::query()
                    ->where('contrat_id', $contratCdiValide->getKey())
                    ->firstOrFail();
                $tachesCdi = $onboardingCdi->taches()->get()->keyBy(
                    fn (OnboardingTache $tache): string => $tache->code->value
                );
                foreach ([TypeTacheOnboarding::Badge, TypeTacheOnboarding::Compte] as $codeTache) {
                    $services['onboardings']->changerStatutTache(
                        $tachesCdi->get($codeTache->value),
                        StatutTache::Fait
                    );
                }
                $responsable = User::query()->first();
                $tacheEnRetard = $tachesCdi->get(TypeTacheOnboarding::Materiel->value);
                $services['onboardings']->assignerTache(
                    $tacheEnRetard,
                    $responsable?->getKey(),
                    $aujourdHui->subDay()
                );

                $contratCddRenouvelable = $services['contrats']->creer($employes[3], [
                    'type' => TypeContrat::Cdd,
                    'date_debut' => $aujourdHui->subYear(),
                    'date_fin' => $aujourdHui->subDay(),
                    'salaire' => 3100,
                    'horaires' => '08h00 - 16h30',
                    'lieu_travail' => 'Nantes',
                ]);
                $this->enregistrerHistorique($contratCddRenouvelable, null, StatutContrat::Cree);
                $services['contrats']->signer($contratCddRenouvelable, $aujourdHui->subYear());
                $this->enregistrerHistorique($contratCddRenouvelable, StatutContrat::Cree, StatutContrat::Signe);
                $services['contrats']->valider($contratCddRenouvelable);
                $this->enregistrerHistorique($contratCddRenouvelable, StatutContrat::Signe, StatutContrat::Valide);
                $contratCddRenouvelable->refresh();
                $services['contrats']->renouveler($contratCddRenouvelable, [
                    'date_debut' => $aujourdHui,
                    'date_fin' => $aujourdHui->addYear(),
                ]);
                $contratRenouvellement = Contrat::query()
                    ->where('contrat_precedent_id', $contratCddRenouvelable->getKey())
                    ->firstOrFail();
                $this->enregistrerHistorique($contratRenouvellement, null, StatutContrat::Cree);

                $contratCddTermine = $services['contrats']->creer($employes[4], [
                    'type' => TypeContrat::Cdd,
                    'date_debut' => $aujourdHui->subDays(100),
                    'date_fin' => $aujourdHui->addDays(265),
                    'salaire' => 2950,
                    'horaires' => '08h00 - 17h00',
                    'lieu_travail' => 'Lille',
                ]);
                $this->enregistrerHistorique($contratCddTermine, null, StatutContrat::Cree);
                $services['contrats']->signer($contratCddTermine, $aujourdHui->subDays(100));
                $this->enregistrerHistorique($contratCddTermine, StatutContrat::Cree, StatutContrat::Signe);
                $services['contrats']->valider($contratCddTermine);
                $this->enregistrerHistorique($contratCddTermine, StatutContrat::Signe, StatutContrat::Valide);
                $onboardingTermine = Onboarding::query()
                    ->where('contrat_id', $contratCddTermine->getKey())
                    ->firstOrFail();

                foreach ($onboardingTermine->taches()->where('statut', '!=', StatutTache::Fait)->get() as $tache) {
                    $services['onboardings']->changerStatutTache($tache, StatutTache::Fait);
                }

                $services['affectations']->affecter(
                    $employes[4],
                    $postePrincipal->getKey(),
                    $departementPrincipal->getKey(),
                    $aujourdHui->subDays(100)
                );
                $services['affectations']->affecter(
                    $employes[4],
                    $posteSecondaire->getKey(),
                    $departementSecondaire->getKey(),
                    $aujourdHui->subDays(10)
                );

                $services['documents']->enregistrerChemin(
                    $contratSigne,
                    "demo/contrats/{$contratSigne->getKey()}/contrat-signe.pdf",
                    TypeDocument::ContratSigne,
                    'Contrat signe (demonstration)'
                );
                $services['documents']->enregistrerChemin(
                    $employes[0],
                    "demo/employes/{$employes[0]->getKey()}/piece-identite.jpg",
                    TypeDocument::PieceIdentite,
                    "Piece d'identite (demonstration)"
                );
            });
        });
    }

    /**
     * @return array<int, Employe>
     */
    private function employesDeDemonstration(): array
    {
        $employes = Employe::query()
            ->doesntHave('contrats')
            ->get()
            ->sortBy(fn (Employe $employe): int => $employe->matricule === null ? 0 : 1)
            ->take(5)
            ->values()
            ->all();

        while (count($employes) < 5) {
            $employes[] = Employe::factory()->create();
        }

        $indexSansMatricule = collect($employes)->search(
            fn (Employe $employe): bool => $employe->matricule === null
        );

        if ($indexSansMatricule !== false && $indexSansMatricule !== 0) {
            $sansMatricule = $employes[$indexSansMatricule];
            unset($employes[$indexSansMatricule]);
            array_unshift($employes, $sansMatricule);
            $employes = array_values($employes);
        }

        return $employes;
    }

    /**
     * @return array{0: Departement, 1: Departement}
     */
    private function departementsDeDemonstration(): array
    {
        $departements = Departement::query()->orderBy('id')->take(2)->get();

        foreach ([
            ['code' => 'DEMO-RH', 'nom' => 'Ressources humaines'],
            ['code' => 'DEMO-ADMIN', 'nom' => 'Administration'],
        ] as $attributs) {
            if ($departements->count() === 2) {
                break;
            }

            $departements->push(Departement::query()->firstOrCreate(
                ['code' => $attributs['code']],
                ['nom' => $attributs['nom'], 'parent_id' => null]
            ));
        }

        return [$departements->get(0), $departements->get(1)];
    }

    private function postePour(Departement $departement, string $intitule): Poste
    {
        return Poste::query()
            ->where('departement_id', $departement->getKey())
            ->first()
            ?? Poste::query()->firstOrCreate(
                ['intitule' => $intitule, 'departement_id' => $departement->getKey()],
                ['description' => 'Poste de demonstration', 'actif' => true]
            );
    }

    private function enregistrerHistorique(
        Contrat $contrat,
        ?StatutContrat $ancienStatut,
        StatutContrat $nouveauStatut
    ): void {
        ContratHistorique::query()->create([
            'contrat_id' => $contrat->getKey(),
            'ancien_statut' => $ancienStatut?->value,
            'nouveau_statut' => $nouveauStatut->value,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
    }
}
