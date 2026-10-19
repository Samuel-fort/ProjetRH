<?php

namespace Tests\Feature\Contrat;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutContrat;
use App\Enums\StatutOnboarding;
use App\Filament\Resources\Contrat\Affectations\AffectationResource;
use App\Filament\Resources\Contrat\Contrats\ContratResource;
use App\Filament\Resources\Contrat\Contrats\Pages\ViewContrat;
use App\Filament\Resources\Contrat\Documents\DocumentResource;
use App\Filament\Resources\Contrat\Onboardings\OnboardingResource;
use App\Filament\Resources\Contrat\Onboardings\Pages\ViewOnboarding;
use App\Filament\Resources\Contrat\Onboardings\RelationManagers\TachesRelationManager;
use App\Models\Contrat\Affectation;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Document;
use App\Models\Contrat\Onboarding;
use App\Models\Contrat\OnboardingTache;
use App\Models\Embauche\Employe;
use App\Models\User;
use App\Policies\AffectationPolicy;
use App\Policies\ContratPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\OnboardingPolicy;
use App\Policies\OnboardingTachePolicy;
use App\Services\Contrat\OnboardingService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class EcransFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'local']);
        Filament::setCurrentPanel('admin');
    }

    public function test_un_utilisateur_authentifie_accede_aux_listes_des_ressources_du_bloc_4(): void
    {
        $this->actingAs($this->utilisateurRh());

        $this->get(ContratResource::getUrl('index'))->assertOk();
        $this->get(AffectationResource::getUrl('index'))->assertOk();
        $this->get(OnboardingResource::getUrl('index'))->assertOk();
        $this->get(DocumentResource::getUrl('index'))->assertOk();
    }

    public function test_la_consultation_du_contrat_affiche_ses_gestionnaires_de_relations(): void
    {
        $contrat = Contrat::factory()->create();

        $response = $this->actingAs($this->utilisateurRh())
            ->get(ContratResource::getUrl('view', ['record' => $contrat]));

        $response->assertOk();
        $response->assertSee('Documents');
        $response->assertSee('Historique des statuts');
    }

    public function test_la_liste_onboarding_affiche_la_progression_des_taches(): void
    {
        $contrat = Contrat::factory()->signe()->create();
        app(OnboardingService::class)->creerPourContrat($contrat);

        $response = $this->actingAs($this->utilisateurRh())
            ->get(OnboardingResource::getUrl('index'));

        $response->assertOk();
        $response->assertSee('1 / 6');
    }

    public function test_l_action_signer_met_a_jour_le_statut_et_attribue_un_matricule(): void
    {
        $utilisateur = $this->utilisateurRh();
        $contrat = Contrat::factory()->create();

        Livewire::actingAs($utilisateur)
            ->test(ViewContrat::class, ['record' => $contrat->getKey()])
            ->callAction('signer', ['date_signature' => '2026-05-01']);

        $this->assertSame(StatutContrat::Signe, $contrat->fresh()->statut);
        $this->assertSame('EMP-2026-0001', Employe::query()->findOrFail($contrat->employe_id)->matricule);
    }

    public function test_l_action_valider_cree_un_onboarding_et_ses_six_taches(): void
    {
        $utilisateur = $this->utilisateurRh();
        $contrat = Contrat::factory()->signe()->create();

        Livewire::actingAs($utilisateur)
            ->test(ViewContrat::class, ['record' => $contrat->getKey()])
            ->callAction('valider');

        $this->assertSame(StatutContrat::Valide, $contrat->fresh()->statut);
        $onboarding = Onboarding::query()->where('contrat_id', $contrat->getKey())->firstOrFail();
        $this->assertSame(6, $onboarding->taches()->count());
    }

    public function test_l_action_renouveler_cree_un_nouveau_contrat_sans_modifier_la_source(): void
    {
        $utilisateur = $this->utilisateurRh();
        $source = Contrat::factory()->cdd()->valide()->create([
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
        ]);
        $attributsSource = $source->getAttributes();
        ksort($attributsSource);

        $composant = Livewire::actingAs($utilisateur)
            ->test(ViewContrat::class, ['record' => $source->getKey()])
            ->callAction('renouveler', [
                'date_debut' => '2027-01-01',
                'date_fin' => '2027-12-31',
            ]);

        $nouveauContrat = Contrat::query()->where('contrat_precedent_id', $source->getKey())->firstOrFail();
        $this->assertSame(StatutContrat::Cree, $nouveauContrat->statut);
        $composant->assertRedirect(ContratResource::getUrl('view', ['record' => $nouveauContrat]));
        $attributsSourceActualises = $source->fresh()->getAttributes();
        ksort($attributsSourceActualises);

        $this->assertSame($attributsSource, $attributsSourceActualises);
    }

    public function test_les_actions_de_transition_ne_sont_visibles_que_au_bon_statut(): void
    {
        $utilisateur = $this->utilisateurRh();
        $contratCree = Contrat::factory()->create();
        $contratSigne = Contrat::factory()->signe()->create();

        Livewire::actingAs($utilisateur)
            ->test(ViewContrat::class, ['record' => $contratCree->getKey()])
            ->assertActionVisible('signer')
            ->assertActionHidden('valider');

        Livewire::actingAs($utilisateur)
            ->test(ViewContrat::class, ['record' => $contratSigne->getKey()])
            ->assertActionHidden('signer')
            ->assertActionVisible('valider');
    }

    public function test_marquer_toutes_les_taches_comme_faites_termine_l_onboarding(): void
    {
        $utilisateur = $this->utilisateurRh();
        $contrat = Contrat::factory()->signe()->create();
        $onboarding = app(OnboardingService::class)->creerPourContrat($contrat);
        $taches = $onboarding->taches()->where('statut', '!=', 'fait')->get();
        $composant = Livewire::actingAs($utilisateur)->test(TachesRelationManager::class, [
            'ownerRecord' => $onboarding,
            'pageClass' => ViewOnboarding::class,
        ]);

        foreach ($taches as $tache) {
            $composant->callTableAction('marquerCommeFaite', $tache);
        }

        $this->assertSame(StatutOnboarding::Termine, $onboarding->fresh()->statut);
    }

    public function test_les_policies_sont_decouvertes_automatiquement_pour_les_modeles_du_bloc_4(): void
    {
        $this->assertInstanceOf(ContratPolicy::class, Gate::getPolicyFor(Contrat::class));
        $this->assertInstanceOf(AffectationPolicy::class, Gate::getPolicyFor(Affectation::class));
        $this->assertInstanceOf(OnboardingPolicy::class, Gate::getPolicyFor(Onboarding::class));
        $this->assertInstanceOf(OnboardingTachePolicy::class, Gate::getPolicyFor(OnboardingTache::class));
        $this->assertInstanceOf(DocumentPolicy::class, Gate::getPolicyFor(Document::class));
    }

    public function test_roles_manager_et_finance_ont_les_acces_de_consultation_attendus(): void
    {
        $manager = User::factory()->avecRole(RoleUtilisateur::Manager)->create();
        $this->actingAs($manager)
            ->get(OnboardingResource::getUrl('index'))
            ->assertOk();
        $this->actingAs($manager)
            ->get(ContratResource::getUrl('index'))
            ->assertForbidden();
        $this->actingAs($manager)
            ->get(DocumentResource::getUrl('create'))
            ->assertForbidden();

        $finance = User::factory()->avecRole(RoleUtilisateur::Finance)->create();
        $this->actingAs($finance)
            ->get(ContratResource::getUrl('index'))
            ->assertOk();
        $this->actingAs($finance)
            ->get(ContratResource::getUrl('create'))
            ->assertForbidden();
    }

    private function utilisateurRh(): User
    {
        return User::factory()->avecRole(RoleUtilisateur::Rh)->create();
    }
}
