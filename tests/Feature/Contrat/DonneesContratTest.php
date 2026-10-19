<?php

namespace Tests\Feature\Contrat;

use App\Enums\StatutContrat;
use App\Models\Besoin\Departement;
use App\Models\Besoin\Poste;
use App\Models\Contrat\Affectation;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Document;
use App\Models\Contrat\Onboarding;
use App\Models\Contrat\OnboardingTache;
use App\Models\Embauche\Employe;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonneesContratTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_factories_creent_les_enregistrements_et_relations_principales(): void
    {
        $contrat = Contrat::factory()->create();
        $this->assertInstanceOf(Employe::class, $contrat->employe);

        $affectation = Affectation::factory()->create();
        $this->assertInstanceOf(Poste::class, $affectation->poste);
        $this->assertInstanceOf(Departement::class, $affectation->departement);

        $onboarding = Onboarding::factory()->create();
        $tache = OnboardingTache::factory()->create([
            'onboarding_id' => $onboarding->id,
        ]);
        $this->assertCount(1, $onboarding->taches);
        $this->assertInstanceOf(Onboarding::class, $tache->onboarding);

        $document = Document::factory()->create();
        $this->assertInstanceOf(Contrat::class, $document->documentable);
    }

    public function test_un_contrat_precedent_ne_peut_etre_reference_quune_seule_fois(): void
    {
        $precedent = Contrat::factory()->create();
        Contrat::factory()->create(['contrat_precedent_id' => $precedent->id]);

        $this->expectException(QueryException::class);

        Contrat::factory()->create(['contrat_precedent_id' => $precedent->id]);
    }

    public function test_un_salarie_ne_peut_avoir_quun_seul_onboarding(): void
    {
        $employe = Employe::factory()->create();
        $contrat = Contrat::factory()->create(['employe_id' => $employe->id]);

        Onboarding::factory()->create([
            'employe_id' => $employe->id,
            'contrat_id' => $contrat->id,
        ]);

        $this->expectException(QueryException::class);

        Onboarding::factory()->create([
            'employe_id' => $employe->id,
            'contrat_id' => $contrat->id,
        ]);
    }

    public function test_trait_employe_expose_historique_contrat_et_affectation_courante(): void
    {
        $employe = Employe::factory()->create();

        $emplacementAncien = '2025-01-01';
        $emplacementRecent = '2026-01-01';
        Contrat::factory()->create([
            'employe_id' => $employe->id,
            'date_debut' => $emplacementAncien,
            'statut' => StatutContrat::Valide,
        ]);
        $contratValideRecent = Contrat::factory()->create([
            'employe_id' => $employe->id,
            'date_debut' => $emplacementRecent,
            'statut' => StatutContrat::Valide,
        ]);
        Contrat::factory()->create([
            'employe_id' => $employe->id,
            'date_debut' => '2026-06-01',
            'statut' => StatutContrat::Cree,
        ]);

        $this->assertCount(3, $employe->contrats);
        $this->assertSame($contratValideRecent->id, $employe->contratEnCours->id);

        $affectationTerminee = Affectation::factory()->create([
            'employe_id' => $employe->id,
            'date_fin' => '2025-12-31',
        ]);
        $affectationCourante = Affectation::factory()->create([
            'employe_id' => $employe->id,
            'date_fin' => null,
        ]);

        $this->assertNotSame($affectationTerminee->id, $employe->affectationCourante->id);
        $this->assertSame($affectationCourante->id, $employe->affectationCourante->id);
    }
}
