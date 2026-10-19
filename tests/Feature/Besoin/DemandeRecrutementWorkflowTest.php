<?php

namespace Tests\Feature\Besoin;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutDemande;
use App\Enums\StatutValidation;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\User;
use App\Services\Besoin\DemandeRecrutementService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandeRecrutementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private DemandeRecrutementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DemandeRecrutementService::class);
    }

    private function utilisateur(RoleUtilisateur $role): User
    {
        return User::factory()->avecRole($role)->create();
    }

    public function test_la_demande_ne_passe_a_recrutement_ouvert_qu_apres_les_4_validations(): void
    {
        $demande = DemandeRecrutement::factory()->create();
        $this->service->soumettre($demande);

        foreach ([RoleUtilisateur::Manager, RoleUtilisateur::Rh, RoleUtilisateur::Finance] as $role) {
            $this->service->valider($demande->refresh(), $this->utilisateur($role));
            $this->assertSame(StatutDemande::EnValidation, $demande->refresh()->statut);
        }

        $this->service->valider($demande->refresh(), $this->utilisateur(RoleUtilisateur::Direction));

        $this->assertSame(StatutDemande::RecrutementOuvert, $demande->refresh()->statut);
    }

    public function test_un_role_non_autorise_ne_peut_pas_valider_une_etape(): void
    {
        $demande = DemandeRecrutement::factory()->create();
        $this->service->soumettre($demande);

        // La premiere etape est "manager" : la finance n'a pas le droit.
        $this->expectException(DomainException::class);

        $this->service->valider($demande->refresh(), $this->utilisateur(RoleUtilisateur::Finance));
    }

    public function test_un_refus_exige_un_commentaire(): void
    {
        $demande = DemandeRecrutement::factory()->create();
        $this->service->soumettre($demande);

        $this->expectException(DomainException::class);

        $this->service->refuser($demande->refresh(), $this->utilisateur(RoleUtilisateur::Manager), '   ');
    }

    public function test_un_refus_passe_la_demande_a_refusee(): void
    {
        $demande = DemandeRecrutement::factory()->create();
        $this->service->soumettre($demande);

        $this->service->refuser($demande->refresh(), $this->utilisateur(RoleUtilisateur::Manager), 'Budget insuffisant.');

        $this->assertSame(StatutDemande::Refusee, $demande->refresh()->statut);
        $this->assertSame(
            StatutValidation::Refusee,
            $demande->validations()->where('etape', 'manager')->first()->statut,
        );
    }

    public function test_les_changements_de_statut_sont_enregistres_dans_l_historique(): void
    {
        $demande = DemandeRecrutement::factory()->create();
        $this->service->soumettre($demande);

        $this->assertSame(1, $demande->historiques()->count());
    }
}
