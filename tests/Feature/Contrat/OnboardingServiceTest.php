<?php

namespace Tests\Feature\Contrat;

use App\Enums\StatutOnboarding;
use App\Enums\StatutTache;
use App\Enums\TypeTacheOnboarding;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Onboarding;
use App\Models\Contrat\OnboardingTache;
use App\Models\User;
use App\Services\Contrat\OnboardingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_checklist_contient_une_fois_chacun_des_six_codes_et_est_creee_pour_un_contrat_signe(): void
    {
        $contrat = Contrat::factory()->signe()->create([
            'date_debut' => '2026-06-01',
            'date_signature' => '2026-05-29',
        ]);

        $onboarding = app(OnboardingService::class)->creerPourContrat($contrat);

        $this->assertSame([
            'contrat_signe',
            'badge',
            'compte',
            'materiel',
            'formation',
            'inscription_paie',
        ], $onboarding->taches()->orderBy('id')->pluck('code')->map(
            fn (TypeTacheOnboarding $code): string => $code->value
        )->all());
        $this->assertSame(6, $onboarding->taches()->distinct('code')->count('code'));
    }

    public function test_seule_la_tache_du_contrat_signe_est_initialement_faite(): void
    {
        $contrat = Contrat::factory()->signe()->create();

        $onboarding = app(OnboardingService::class)->creerPourContrat($contrat);

        $statuts = $onboarding->taches()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (OnboardingTache $tache): array => [$tache->code->value => $tache->statut->value])
            ->all();

        $this->assertSame(StatutTache::Fait->value, $statuts[TypeTacheOnboarding::ContratSigne->value]);
        $this->assertSame(5, count(array_filter($statuts, fn (string $statut): bool => $statut === StatutTache::AFaire->value)));
    }

    public function test_echeances_sont_calculees_depuis_les_dates_du_contrat(): void
    {
        $contrat = Contrat::factory()->signe()->create([
            'date_debut' => '2026-06-01',
            'date_signature' => '2026-05-29',
        ]);

        $onboarding = app(OnboardingService::class)->creerPourContrat($contrat);
        $echeances = $onboarding->taches()
            ->get()
            ->mapWithKeys(fn (OnboardingTache $tache): array => [
                $tache->code->value => $tache->date_echeance?->toDateString(),
            ])
            ->all();

        $this->assertSame('2026-05-29', $echeances[TypeTacheOnboarding::ContratSigne->value]);
        $this->assertSame('2026-06-01', $echeances[TypeTacheOnboarding::Badge->value]);
        $this->assertSame('2026-06-01', $echeances[TypeTacheOnboarding::Compte->value]);
        $this->assertSame('2026-06-01', $echeances[TypeTacheOnboarding::Materiel->value]);
        $this->assertSame('2026-06-08', $echeances[TypeTacheOnboarding::InscriptionPaie->value]);
        $this->assertSame('2026-06-15', $echeances[TypeTacheOnboarding::Formation->value]);
    }

    public function test_onboarding_se_termine_quand_toutes_les_taches_sont_faites_et_se_rouvre(): void
    {
        $contrat = Contrat::factory()->signe()->create();
        $service = app(OnboardingService::class);
        $onboarding = $service->creerPourContrat($contrat);

        foreach ($onboarding->taches()->where('statut', '!=', StatutTache::Fait->value)->get() as $tache) {
            $service->changerStatutTache($tache, StatutTache::Fait);
        }

        $termine = $onboarding->fresh();
        $this->assertSame(StatutOnboarding::Termine, $termine->statut);
        $this->assertSame(CarbonImmutable::today()->toDateString(), $termine->date_fin->toDateString());

        $service->changerStatutTache($termine->taches()->firstOrFail(), StatutTache::EnCours);

        $this->assertSame(StatutOnboarding::EnCours, $termine->fresh()->statut);
        $this->assertNull($termine->fresh()->date_fin);
    }

    public function test_creer_pour_contrat_est_idempotent_pour_un_salarie(): void
    {
        $contrat = Contrat::factory()->signe()->create();
        $service = app(OnboardingService::class);
        $premier = $service->creerPourContrat($contrat);
        $secondContrat = Contrat::factory()->signe()->create(['employe_id' => $contrat->employe_id]);

        $second = $service->creerPourContrat($secondContrat);

        $this->assertSame($premier->getKey(), $second->getKey());
        $this->assertSame(1, Onboarding::query()->where('employe_id', $contrat->employe_id)->count());
    }

    public function test_assigner_tache_met_a_jour_le_responsable_et_lecheance(): void
    {
        $tache = OnboardingTache::factory()->create();
        $responsable = User::factory()->create();

        $assignee = app(OnboardingService::class)->assignerTache(
            $tache,
            $responsable->getKey(),
            CarbonImmutable::parse('2026-07-01')
        );

        $this->assertSame($responsable->getKey(), $assignee->responsable_id);
        $this->assertSame('2026-07-01', $assignee->date_echeance->toDateString());
    }
}
