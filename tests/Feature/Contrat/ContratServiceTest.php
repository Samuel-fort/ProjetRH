<?php

namespace Tests\Feature\Contrat;

use App\Enums\StatutContrat;
use App\Enums\TypeContrat;
use App\Enums\TypeDocument;
use App\Exceptions\Contrat\RenouvellementImpossible;
use App\Exceptions\Contrat\TransitionContratInvalide;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Onboarding;
use App\Services\Contrat\ContratService;
use App\Services\Contrat\MatriculeService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Contrat\Concerns\CreeDonneesContrat;
use Tests\TestCase;

class ContratServiceTest extends TestCase
{
    use CreeDonneesContrat;
    use RefreshDatabase;

    public function test_creer_enregistre_un_contrat_sans_matricule_affectation_ni_onboarding(): void
    {
        $employe = $this->creerEmploye();

        $contrat = app(ContratService::class)->creer($employe, $this->donneesContrat());

        $this->assertSame(StatutContrat::Cree, $contrat->statut);
        $this->assertNull($employe->fresh()->matricule);
        $this->assertDatabaseCount('contrat_affectations', 0);
        $this->assertDatabaseCount('contrat_onboardings', 0);
    }

    public function test_le_matricule_est_attribue_uniquement_apres_la_signature(): void
    {
        $employe = $this->creerEmploye();
        $service = app(ContratService::class);
        $contrat = $service->creer($employe, $this->donneesContrat());

        $this->assertNull($employe->fresh()->matricule);
        $this->assertSame(StatutContrat::Cree, $contrat->fresh()->statut);

        $service->signer($contrat, CarbonImmutable::parse('2026-01-02'));

        $this->assertSame('EMP-2026-0001', $employe->fresh()->matricule);
        $this->assertSame(StatutContrat::Signe, $contrat->fresh()->statut);
    }

    public function test_signer_renseigne_le_statut_la_date_et_le_matricule(): void
    {
        $employe = $this->creerEmploye();
        $contrat = app(ContratService::class)->creer($employe, $this->donneesContrat());

        $signe = app(ContratService::class)->signer($contrat, CarbonImmutable::parse('2026-02-03'));

        $this->assertSame(StatutContrat::Signe, $signe->statut);
        $this->assertSame('2026-02-03', $signe->date_signature->toDateString());
        $this->assertSame('EMP-2026-0001', $employe->fresh()->matricule);
    }

    public function test_un_contrat_deja_signe_ne_peut_pas_etre_signe_une_seconde_fois(): void
    {
        $contrat = Contrat::factory()->signe()->create();

        $this->expectException(TransitionContratInvalide::class);

        app(ContratService::class)->signer($contrat);
    }

    public function test_un_contrat_cree_ne_peut_pas_etre_valide(): void
    {
        $contrat = Contrat::factory()->create();

        $this->expectException(TransitionContratInvalide::class);

        app(ContratService::class)->valider($contrat);
    }

    public function test_valider_un_contrat_signe_cree_un_onboarding_avec_ses_six_taches(): void
    {
        $contrat = Contrat::factory()->signe()->create([
            'date_debut' => '2026-05-01',
            'date_signature' => '2026-04-30',
        ]);

        $valide = app(ContratService::class)->valider($contrat);
        $onboarding = Onboarding::query()->where('employe_id', $contrat->employe_id)->firstOrFail();

        $this->assertSame(StatutContrat::Valide, $valide->statut);
        $this->assertSame('2026-04-30', $onboarding->taches()->where('code', 'contrat_signe')->firstOrFail()->date_echeance->toDateString());
        $this->assertDatabaseCount('contrat_onboarding_taches', 6);
    }

    public function test_renouveler_conserve_le_contrat_source_et_cree_un_nouveau_contrat_lie(): void
    {
        $source = Contrat::factory()->valide()->create([
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
        ]);
        $attributsAvant = $source->fresh()->getAttributes();
        ksort($attributsAvant);

        $nouveau = app(ContratService::class)->renouveler($source, [
            'date_debut' => '2027-01-01',
            'date_fin' => '2027-12-31',
        ]);

        $attributsApres = $source->fresh()->getAttributes();
        ksort($attributsApres);
        $this->assertSame($attributsAvant, $attributsApres);
        $this->assertSame($source->getKey(), $nouveau->contrat_precedent_id);
        $this->assertSame(StatutContrat::Cree, $nouveau->statut);
        $this->assertSame(TypeContrat::Renouvellement, $nouveau->type);
    }

    public function test_un_contrat_ne_peut_pas_etre_renouvele_deux_fois(): void
    {
        $source = Contrat::factory()->valide()->create(['date_fin' => '2026-12-31']);
        $service = app(ContratService::class);
        $service->renouveler($source, ['date_debut' => '2027-01-01', 'date_fin' => '2027-12-31']);

        $this->expectException(RenouvellementImpossible::class);

        $service->renouveler($source, ['date_debut' => '2028-01-01', 'date_fin' => '2028-12-31']);
    }

    public function test_un_cdi_ne_peut_pas_etre_renouvele(): void
    {
        $source = Contrat::factory()->cdi()->valide()->create();

        $this->expectException(RenouvellementImpossible::class);

        app(ContratService::class)->renouveler($source, ['date_debut' => '2027-01-01']);
    }

    public function test_un_contrat_non_valide_ne_peut_pas_etre_renouvele(): void
    {
        $source = Contrat::factory()->create();

        $this->expectException(RenouvellementImpossible::class);

        app(ContratService::class)->renouveler($source, ['date_debut' => '2027-01-01', 'date_fin' => '2027-12-31']);
    }

    public function test_la_date_debut_du_renouvellement_doit_suivre_la_fin_du_contrat_source(): void
    {
        $source = Contrat::factory()->valide()->create(['date_fin' => '2026-12-31']);

        try {
            app(ContratService::class)->renouveler($source, [
                'date_debut' => '2026-12-31',
                'date_fin' => '2027-12-31',
            ]);
            $this->fail('Une date de début antérieure ou égale à la fin du contrat source doit être refusée.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('date_debut', $exception->errors());
        }
    }

    public function test_signer_et_valider_un_renouvellement_ne_creent_pas_de_second_matricule_ni_onboarding(): void
    {
        $source = Contrat::factory()->valide()->create([
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
        ]);
        $employe = $source->employe;
        $matricule = app(MatriculeService::class)
            ->attribuer($employe, CarbonImmutable::parse('2026-01-02'));
        $onboarding = Onboarding::factory()->create([
            'employe_id' => $employe->getKey(),
            'contrat_id' => $source->getKey(),
        ]);
        $service = app(ContratService::class);
        $nouveau = $service->renouveler($source, [
            'date_debut' => '2027-01-01',
            'date_fin' => '2027-12-31',
        ]);

        $service->signer($nouveau, CarbonImmutable::parse('2027-01-01'));
        $service->valider($nouveau);

        $this->assertSame($matricule, $employe->fresh()->matricule);
        $this->assertSame(1, $employe->onboarding()->count());
        $this->assertSame($onboarding->getKey(), $employe->onboarding()->firstOrFail()->getKey());
    }

    public function test_un_cdi_ne_peut_pas_avoir_de_date_de_fin(): void
    {
        $this->expectException(ValidationException::class);

        app(ContratService::class)->creer($this->creerEmploye(), $this->donneesContrat([
            'type' => TypeContrat::Cdi,
            'date_fin' => '2026-12-31',
        ]));
    }

    public function test_un_cdd_doit_avoir_une_date_de_fin(): void
    {
        $this->expectException(ValidationException::class);

        app(ContratService::class)->creer($this->creerEmploye(), $this->donneesContrat([
            'date_fin' => null,
        ]));
    }

    public function test_la_date_de_fin_doit_etre_posterieure_a_la_date_de_debut(): void
    {
        $this->expectException(ValidationException::class);

        app(ContratService::class)->creer($this->creerEmploye(), $this->donneesContrat([
            'date_fin' => '2026-01-01',
        ]));
    }

    public function test_un_contrat_signe_ou_valide_ne_peut_pas_etre_modifie(): void
    {
        $service = app(ContratService::class);
        $signe = Contrat::factory()->signe()->create();
        $valide = Contrat::factory()->valide()->create();

        try {
            $service->modifier($signe, $this->donneesContrat());
            $this->fail('Un contrat signé ne doit pas être modifiable.');
        } catch (TransitionContratInvalide) {
            $this->assertSame(StatutContrat::Signe, $signe->fresh()->statut);
        }

        $this->expectException(TransitionContratInvalide::class);
        $service->modifier($valide, $this->donneesContrat());
    }

    public function test_creation_depuis_proposition_cree_une_affectation_et_est_idempotente(): void
    {
        $employe = $this->creerEmploye();
        $liens = $this->creerPoste();
        $donnees = [
            'proposition_embauche_id' => 912,
            'poste_id' => $liens['poste']->getKey(),
            'departement_id' => $liens['departement']->getKey(),
            'salaire' => 4200,
            'horaires' => '09h00 - 18h00',
            'lieu_travail' => 'Antananarivo',
            'date_debut' => '2026-09-01',
        ];
        $service = app(ContratService::class);

        $premier = $service->creerDepuisProposition($employe, $donnees);
        $second = $service->creerDepuisProposition($employe, $donnees);

        $this->assertSame($premier->getKey(), $second->getKey());
        $this->assertSame(TypeContrat::Cdi, $premier->type);
        $this->assertNull($premier->date_fin);
        $this->assertNull($employe->fresh()->matricule);
        $this->assertDatabaseCount('contrat_contrats', 1);
        $this->assertDatabaseCount('contrat_affectations', 1);
    }

    public function test_generateur_pdf_absent_ne_cree_aucun_document(): void
    {
        if (class_exists('App\\Services\\Embauche\\GenererContratPdf')) {
            $this->markTestSkipped('Le générateur PDF est présent ; le test du faux générateur couvre son intégration.');
        }

        $contrat = Contrat::factory()->create();

        app(ContratService::class)->declencherGenerationPdf($contrat);

        $this->assertDatabaseCount('contrat_documents', 0);
    }

    public function test_generateur_pdf_peut_enregistrer_un_document_lorsquil_est_present(): void
    {
        $classeGenerateur = 'App\\Services\\Embauche\\GenererContratPdf';
        if (! class_exists($classeGenerateur)) {
            $this->markTestSkipped('Le générateur PDF du Bloc 3 n’est pas encore disponible.');
        }

        $contrat = Contrat::factory()->create();
        $generateur = $this->mock($classeGenerateur);
        $generateur->shouldReceive('generer')->once()->with($contrat)->andReturn('documents/contrat.pdf');
        app()->instance($classeGenerateur, $generateur);

        app(ContratService::class)->declencherGenerationPdf($contrat);

        $this->assertDatabaseHas('contrat_documents', [
            'documentable_id' => $contrat->getKey(),
            'documentable_type' => $contrat->getMorphClass(),
            'type' => TypeDocument::ContratGenere->value,
            'chemin' => 'documents/contrat.pdf',
        ]);
    }
}
