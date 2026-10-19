<?php

namespace Tests\Feature\Recrutement;

use App\Enums\StatutOffre;
use App\Models\Besoin\DemandeRecrutement;
use App\Services\Recrutement\OffreService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du Bloc 2 : règles sur les offres d'emploi.
 * RefreshDatabase = base vide et propre avant chaque test.
 * NB : ces tests utilisent le modèle/la factory DemandeRecrutement du Bloc 1 (Samuel).
 */
class OffreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_impossible_de_creer_une_offre_depuis_une_demande_non_validee(): void
    {
        // Pour chaque statut du workflow du Bloc 1 qui n'est PAS "recrutement_ouvert".
        foreach (['brouillon', 'en_validation', 'validee', 'refusee'] as $statut) {
            $demande = DemandeRecrutement::factory()->create(['statut' => $statut]);

            try {
                app(OffreService::class)->creerDepuisDemande($demande, 'Développeur');
                $this->fail("Une offre a été créée depuis une demande « {$statut} ».");
            } catch (DomainException) {
                $this->assertDatabaseCount('offre_emplois', 0);
            }
        }
    }

    public function test_une_offre_se_cree_depuis_une_demande_ouverte_puis_se_publie_et_se_cloture(): void
    {
        $service = app(OffreService::class);
        $demande = DemandeRecrutement::factory()->create(['statut' => 'recrutement_ouvert']);

        $offre = $service->creerDepuisDemande($demande, 'Développeur Laravel', 'Description');
        $this->assertSame(StatutOffre::Brouillon, $offre->statut);

        $service->publier($offre);
        $this->assertSame(StatutOffre::Publiee, $offre->fresh()->statut);
        $this->assertNotNull($offre->fresh()->date_publication);

        $service->cloturer($offre);
        $this->assertSame(StatutOffre::Cloturee, $offre->fresh()->statut);
    }

    public function test_on_ne_publie_pas_deux_fois_et_on_ne_cloture_pas_un_brouillon(): void
    {
        $service = app(OffreService::class);
        $offre = $service->creerDepuisDemande(
            DemandeRecrutement::factory()->create(['statut' => 'recrutement_ouvert']),
            'Comptable'
        );

        $this->expectException(DomainException::class);
        $service->cloturer($offre); // brouillon -> clôturée interdit
    }
}
