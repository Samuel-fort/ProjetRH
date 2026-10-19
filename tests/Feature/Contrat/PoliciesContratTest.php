<?php

namespace Tests\Feature\Contrat;

use App\Enums\RoleUtilisateur;
use App\Models\Contrat\Affectation;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Document;
use App\Models\Contrat\Onboarding;
use App\Models\Contrat\OnboardingTache;
use App\Models\User;
use App\Policies\AffectationPolicy;
use App\Policies\Concerns\VerifieRolesContrat;
use App\Policies\ContratPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\OnboardingPolicy;
use App\Policies\OnboardingTachePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PoliciesContratTest extends TestCase
{
    use RefreshDatabase;

    public function test_contrat_policy_applique_la_matrice_des_roles(): void
    {
        $contrat = Contrat::factory()->valide()->create(['date_fin' => '2026-12-31']);

        $this->assertRoleMatrix(new ContratPolicy, [
            'viewAny' => ['finance', 'manager'],
            'view' => ['direction', 'manager'],
            'create' => ['rh', 'finance'],
            'signer' => ['rh', 'finance'],
            'valider' => ['admin', 'manager'],
            'renouveler' => ['rh', 'direction'],
        ], [$contrat]);

        foreach (['delete', 'restore', 'forceDelete'] as $ability) {
            $this->assertFalse((new ContratPolicy)->{$ability}($this->userWithRole('admin'), $contrat));
        }

        $modifiable = Contrat::factory()->create();
        $this->assertTrue((new ContratPolicy)->update($this->userWithRole('admin'), $modifiable));
        $this->assertFalse((new ContratPolicy)->update($this->userWithRole('direction'), $modifiable));
    }

    public function test_affectation_policy_applique_la_matrice_des_roles(): void
    {
        $affectation = Affectation::factory()->create();
        $this->assertRoleMatrix(new AffectationPolicy, [
            'viewAny' => ['manager', 'finance'],
            'view' => ['direction', 'finance'],
            'create' => ['rh', 'direction'],
            'update' => ['admin', 'manager'],
        ], [$affectation]);

        $this->assertFalse((new AffectationPolicy)->delete($this->userWithRole('admin'), $affectation));
    }

    public function test_onboarding_policy_applique_la_matrice_des_roles(): void
    {
        $onboarding = Onboarding::factory()->create();
        $this->assertRoleMatrix(new OnboardingPolicy, [
            'viewAny' => ['manager', 'finance'],
            'view' => ['direction', 'finance'],
        ], [$onboarding]);

        $policy = new OnboardingPolicy;
        $rh = $this->userWithRole('rh');
        $this->assertFalse($policy->create($rh));
        $this->assertFalse($policy->update($rh, $onboarding));
        $this->assertFalse($policy->delete($rh, $onboarding));
    }

    public function test_onboarding_tache_policy_applique_la_matrice_et_le_role_du_responsable(): void
    {
        $tache = OnboardingTache::factory()->create();
        $this->assertRoleMatrix(new OnboardingTachePolicy, [
            'viewAny' => ['manager', 'finance'],
            'view' => ['direction', 'finance'],
            'update' => ['admin', 'direction'],
        ], [$tache]);

        $policy = new OnboardingTachePolicy;
        $rh = $this->userWithRole('rh');
        $this->assertFalse($policy->create($rh));
        $this->assertFalse($policy->delete($rh, $tache));
    }

    public function test_responsable_assigne_peut_modifier_sa_tache_sans_role_rh_ni_admin(): void
    {
        $responsable = User::factory()->avecRole(RoleUtilisateur::Manager)->create();
        $tache = OnboardingTache::factory()->create(['responsable_id' => $responsable->getKey()]);

        $this->assertTrue((new OnboardingTachePolicy)->update($responsable, $tache));
    }

    public function test_document_policy_applique_la_matrice_des_roles(): void
    {
        $document = Document::factory()->create();
        $this->assertRoleMatrix(new DocumentPolicy, [
            'viewAny' => ['rh', 'direction'],
            'view' => ['admin', 'direction'],
            'create' => ['rh', 'manager'],
        ], [$document]);

        $policy = new DocumentPolicy;
        $admin = $this->userWithRole('admin');
        $this->assertFalse($policy->update($admin, $document));
        $this->assertFalse($policy->delete($admin, $document));
    }

    public function test_role_nul_explicitement_present_est_refuse_partout(): void
    {
        $user = User::factory()->make(['role' => null]);
        $roles = new class
        {
            use VerifieRolesContrat;
        };
        $contrat = Contrat::factory()->create();
        $contratRenouvelable = Contrat::factory()->valide()->create([
            'date_fin' => '2026-12-31',
        ]);
        $affectation = Affectation::factory()->create();
        $onboarding = Onboarding::factory()->create();
        $tache = OnboardingTache::factory()->create();
        $document = Document::factory()->create();

        $this->assertFalse($roles->aRole($user, 'rh', 'admin', 'direction', 'finance', 'manager'));
        $this->assertFalse((new ContratPolicy)->viewAny($user));
        $this->assertFalse((new ContratPolicy)->view($user, $contrat));
        $this->assertFalse((new ContratPolicy)->create($user));
        $this->assertFalse((new ContratPolicy)->update($user, $contrat));
        $this->assertFalse((new ContratPolicy)->signer($user, $contrat));
        $this->assertFalse((new ContratPolicy)->valider($user, $contrat));
        $this->assertFalse((new ContratPolicy)->renouveler($user, $contratRenouvelable));

        $this->assertFalse((new AffectationPolicy)->viewAny($user));
        $this->assertFalse((new AffectationPolicy)->view($user, $affectation));
        $this->assertFalse((new AffectationPolicy)->create($user));
        $this->assertFalse((new AffectationPolicy)->update($user, $affectation));

        $this->assertFalse((new OnboardingPolicy)->viewAny($user));
        $this->assertFalse((new OnboardingPolicy)->view($user, $onboarding));
        $this->assertFalse((new OnboardingTachePolicy)->view($user, $tache));
        $this->assertFalse((new OnboardingTachePolicy)->viewAny($user));
        $this->assertFalse((new OnboardingTachePolicy)->update($user, $tache));

        $this->assertFalse((new DocumentPolicy)->viewAny($user));
        $this->assertFalse((new DocumentPolicy)->view($user, $document));
        $this->assertFalse((new DocumentPolicy)->create($user));
    }

    public function test_role_backed_enum_est_lu_depuis_sa_valeur(): void
    {
        $user = User::factory()->make();
        $user->forceFill(['role' => RoleUtilisateur::Rh]);
        $roles = new class
        {
            use VerifieRolesContrat;
        };

        $this->assertSame('rh', $roles->roleDe($user));
    }

    public function test_un_utilisateur_sans_role_est_refuse_par_toutes_les_policies(): void
    {
        $user = new User;
        $contrat = Contrat::factory()->valide()->create(['date_fin' => '2026-12-31']);
        $affectation = Affectation::factory()->create();
        $onboarding = Onboarding::factory()->create();
        $tache = OnboardingTache::factory()->create();
        $document = Document::factory()->create();

        $this->assertFalse((new ContratPolicy)->viewAny($user));
        $this->assertFalse((new ContratPolicy)->view($user, $contrat));
        $this->assertFalse((new ContratPolicy)->create($user));
        $this->assertFalse((new ContratPolicy)->update($user, $contrat));
        $this->assertFalse((new ContratPolicy)->signer($user, $contrat));
        $this->assertFalse((new ContratPolicy)->valider($user, $contrat));
        $this->assertFalse((new ContratPolicy)->renouveler($user, $contrat));

        $this->assertFalse((new AffectationPolicy)->viewAny($user));
        $this->assertFalse((new AffectationPolicy)->view($user, $affectation));
        $this->assertFalse((new AffectationPolicy)->create($user));
        $this->assertFalse((new AffectationPolicy)->update($user, $affectation));

        $this->assertFalse((new OnboardingPolicy)->viewAny($user));
        $this->assertFalse((new OnboardingPolicy)->view($user, $onboarding));
        $this->assertFalse((new OnboardingTachePolicy)->viewAny($user));
        $this->assertFalse((new OnboardingTachePolicy)->view($user, $tache));
        $this->assertFalse((new OnboardingTachePolicy)->update($user, $tache));

        $this->assertFalse((new DocumentPolicy)->viewAny($user));
        $this->assertFalse((new DocumentPolicy)->view($user, $document));
        $this->assertFalse((new DocumentPolicy)->create($user));
    }

    public function test_un_contrat_signe_ne_peut_pas_etre_modifie_meme_par_un_admin(): void
    {
        $contrat = Contrat::factory()->signe()->create();

        $this->assertFalse((new ContratPolicy)->update($this->userWithRole('admin'), $contrat));
    }

    public function test_manager_peut_consulter_un_onboarding_mais_pas_signer_valider_renouveler_ou_televerser(): void
    {
        $manager = User::factory()->avecRole(RoleUtilisateur::Manager)->make();
        $contratCree = Contrat::factory()->create();
        $contratSigne = Contrat::factory()->signe()->create();
        $contratRenouvelable = Contrat::factory()->valide()->create(['date_fin' => '2026-12-31']);
        $onboarding = Onboarding::factory()->create();

        $contratPolicy = new ContratPolicy;
        $this->assertFalse($contratPolicy->signer($manager, $contratCree));
        $this->assertFalse($contratPolicy->valider($manager, $contratSigne));
        $this->assertFalse($contratPolicy->renouveler($manager, $contratRenouvelable));
        $this->assertFalse((new DocumentPolicy)->create($manager));
        $this->assertTrue((new OnboardingPolicy)->view($manager, $onboarding));
    }

    public function test_finance_peut_consulter_un_contrat_mais_pas_le_modifier(): void
    {
        $finance = User::factory()->avecRole(RoleUtilisateur::Finance)->make();
        $contrat = Contrat::factory()->create();
        $policy = new ContratPolicy;

        $this->assertTrue($policy->viewAny($finance));
        $this->assertTrue($policy->view($finance, $contrat));
        $this->assertFalse($policy->create($finance));
        $this->assertFalse($policy->update($finance, $contrat));
        $this->assertFalse($policy->signer($finance, $contrat));
        $this->assertFalse($policy->valider($finance, $contrat));
        $this->assertFalse($policy->renouveler($finance, $contrat));
    }

    public function test_les_cinq_policies_sont_decouvertes_automatiquement(): void
    {
        $policies = [
            Contrat::class => ContratPolicy::class,
            Affectation::class => AffectationPolicy::class,
            Onboarding::class => OnboardingPolicy::class,
            OnboardingTache::class => OnboardingTachePolicy::class,
            Document::class => DocumentPolicy::class,
        ];

        foreach ($policies as $modele => $policy) {
            $this->assertInstanceOf($policy, Gate::getPolicyFor($modele));
        }
    }

    /**
     * @param  array<string, array{string, string}>  $matrice
     * @param  array<int, mixed>  $arguments
     */
    private function assertRoleMatrix(object $policy, array $matrice, array $arguments = []): void
    {
        foreach ($matrice as $ability => [$roleAutorise, $roleRefuse]) {
            $this->assertTrue($policy->{$ability}($this->userWithRole($roleAutorise), ...$arguments), "{$ability} doit autoriser {$roleAutorise}.");
            $this->assertFalse($policy->{$ability}($this->userWithRole($roleRefuse), ...$arguments), "{$ability} doit refuser {$roleRefuse}.");
        }
    }

    private function userWithRole(?string $role): User
    {
        return User::factory()->make([
            'role' => $role === null ? null : RoleUtilisateur::from($role),
        ]);
    }
}
