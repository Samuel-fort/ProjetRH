<?php

namespace App\Services\Contrat;

use App\Enums\StatutOnboarding;
use App\Enums\StatutTache;
use App\Enums\TypeTacheOnboarding;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Onboarding;
use App\Models\Contrat\OnboardingTache;
use App\Models\User;
use App\Notifications\Contrat\TacheOnboardingAssignee;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OnboardingService
{
    private const int JOURS_AVANT_INSCRIPTION_PAIE = 7;

    private const int JOURS_AVANT_FORMATION = 14;

    public function creerPourContrat(Contrat $contrat): ?Onboarding
    {
        return DB::transaction(function () use ($contrat): ?Onboarding {
            $contratVerrouille = Contrat::query()
                ->whereKey($contrat->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $contratVerrouille->employe()
                ->lockForUpdate()
                ->firstOrFail();

            $existant = Onboarding::query()
                ->where('employe_id', $contratVerrouille->employe_id)
                ->first();

            if ($existant !== null) {
                return $existant;
            }

            $onboarding = Onboarding::query()->create([
                'employe_id' => $contratVerrouille->employe_id,
                'contrat_id' => $contratVerrouille->getKey(),
                'statut' => StatutOnboarding::EnCours,
            ]);

            foreach (TypeTacheOnboarding::ordonnees() as $type) {
                $dateEcheance = $this->dateEcheance($contratVerrouille, $type);
                $estTerminee = $type === TypeTacheOnboarding::ContratSigne;

                OnboardingTache::query()->create([
                    'onboarding_id' => $onboarding->getKey(),
                    'code' => $type,
                    'libelle' => $type->label(),
                    'statut' => $estTerminee ? StatutTache::Fait : StatutTache::AFaire,
                    'responsable_id' => null,
                    'date_echeance' => $dateEcheance?->toDateString(),
                    'date_realisation' => $estTerminee ? ($contratVerrouille->date_signature ?? now()) : null,
                ]);
            }

            return $this->recalculerStatut($onboarding);
        });
    }

    public function changerStatutTache(OnboardingTache $tache, StatutTache $statut): OnboardingTache
    {
        return DB::transaction(function () use ($tache, $statut): OnboardingTache {
            $tacheVerrouillee = OnboardingTache::query()
                ->whereKey($tache->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $tacheVerrouillee->statut = $statut;
            $tacheVerrouillee->date_realisation = $statut === StatutTache::Fait ? now() : null;
            $tacheVerrouillee->save();

            $this->recalculerStatut($tacheVerrouillee->onboarding);

            return $tacheVerrouillee;
        });
    }

    public function assignerTache(
        OnboardingTache $tache,
        ?int $responsableId,
        ?CarbonInterface $echeance = null
    ): OnboardingTache {
        $tache->responsable_id = $responsableId;

        if ($echeance !== null) {
            $tache->date_echeance = $echeance->toDateString();
        }

        $tache->save();

        if ($responsableId !== null) {
            $responsable = User::query()->findOrFail($responsableId);
            Notification::send($responsable, new TacheOnboardingAssignee($tache));
        }

        return $tache;
    }

    public function recalculerStatut(Onboarding $onboarding): Onboarding
    {
        return DB::transaction(function () use ($onboarding): Onboarding {
            $onboardingVerrouille = Onboarding::query()
                ->whereKey($onboarding->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $nombreTaches = $onboardingVerrouille->taches()->count();
            $toutesFaites = $nombreTaches > 0
                && $onboardingVerrouille->taches()
                    ->where('statut', StatutTache::Fait)
                    ->count() === $nombreTaches;

            $onboardingVerrouille->statut = $toutesFaites
                ? StatutOnboarding::Termine
                : StatutOnboarding::EnCours;
            $onboardingVerrouille->date_fin = $toutesFaites ? now()->toDateString() : null;
            $onboardingVerrouille->save();

            return $onboardingVerrouille;
        });
    }

    private function dateEcheance(Contrat $contrat, TypeTacheOnboarding $type): ?CarbonImmutable
    {
        return match ($type) {
            TypeTacheOnboarding::ContratSigne => $contrat->date_signature === null
                ? null
                : CarbonImmutable::parse($contrat->date_signature),
            TypeTacheOnboarding::Badge,
            TypeTacheOnboarding::Compte,
            TypeTacheOnboarding::Materiel => CarbonImmutable::parse($contrat->date_debut),
            TypeTacheOnboarding::InscriptionPaie => CarbonImmutable::parse($contrat->date_debut)
                ->addDays(self::JOURS_AVANT_INSCRIPTION_PAIE),
            TypeTacheOnboarding::Formation => CarbonImmutable::parse($contrat->date_debut)
                ->addDays(self::JOURS_AVANT_FORMATION),
        };
    }
}
