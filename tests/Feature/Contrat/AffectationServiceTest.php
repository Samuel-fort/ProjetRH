<?php

namespace Tests\Feature\Contrat;

use App\Services\Contrat\AffectationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Contrat\Concerns\CreeDonneesContrat;
use Tests\TestCase;

class AffectationServiceTest extends TestCase
{
    use CreeDonneesContrat;
    use RefreshDatabase;

    public function test_premiere_affectation_reste_en_cours(): void
    {
        $employe = $this->creerEmploye();
        $liens = $this->creerPoste();

        $affectation = app(AffectationService::class)->affecter(
            $employe,
            $liens['poste']->getKey(),
            $liens['departement']->getKey(),
            CarbonImmutable::parse('2026-01-10')
        );

        $this->assertNull($affectation->date_fin);
        $this->assertSame(1, $employe->affectations()->enCours()->count());
    }

    public function test_nouvelle_affectation_cloture_et_conserve_la_precedente(): void
    {
        $employe = $this->creerEmploye();
        $premierPoste = $this->creerPoste();
        $secondPoste = $this->creerPoste();
        $service = app(AffectationService::class);
        $precedente = $service->affecter(
            $employe,
            $premierPoste['poste']->getKey(),
            $premierPoste['departement']->getKey(),
            CarbonImmutable::parse('2026-01-01')
        );

        $courante = $service->affecter(
            $employe,
            $secondPoste['poste']->getKey(),
            $secondPoste['departement']->getKey(),
            CarbonImmutable::parse('2026-03-01')
        );

        $this->assertSame('2026-02-28', $precedente->fresh()->date_fin->toDateString());
        $this->assertSame(2, $employe->affectations()->count());
        $this->assertNull($courante->date_fin);
        $this->assertSame(1, $employe->affectations()->enCours()->count());
    }

    public function test_date_debut_non_posterieure_a_laffectation_en_cours_est_refusee(): void
    {
        $employe = $this->creerEmploye();
        $poste = $this->creerPoste();
        $service = app(AffectationService::class);
        $service->affecter(
            $employe,
            $poste['poste']->getKey(),
            $poste['departement']->getKey(),
            CarbonImmutable::parse('2026-02-01')
        );
        $autrePoste = $this->creerPoste();

        try {
            $service->affecter(
                $employe,
                $autrePoste['poste']->getKey(),
                $autrePoste['departement']->getKey(),
                CarbonImmutable::parse('2026-02-01')
            );
            $this->fail('Une affectation ne peut pas commencer à la même date que l’affectation en cours.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('date_debut', $exception->errors());
        }

        $this->assertSame(1, $employe->affectations()->enCours()->count());
    }
}
