<?php

namespace Tests\Feature\Contrat;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutContrat;
use App\Enums\StatutOnboarding;
use App\Enums\TypeContrat;
use App\Filament\Resources\Contrat\Contrats\ContratResource;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Onboarding;
use App\Models\Embauche\Employe;
use App\Models\User;
use App\Policies\ContratPolicy;
use App\Policies\OnboardingPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DatabaseSeederContratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'local']);
        Filament::setCurrentPanel('admin');
    }

    public function test_database_seeder_installe_les_roles_et_scenarios_du_bloc_4_sans_doublons(): void
    {
        Notification::fake();
        Filament::setCurrentPanel('admin');

        $this->seed();

        $this->assertDatabaseCount('users', 5);
        $this->assertSame(5, Employe::query()->count());
        $this->assertSame(6, Contrat::query()->count());
        $this->assertSame(3, Onboarding::query()->count());
        $this->assertSame(13, DB::table('contrat_contrat_historiques')->count());
        $this->assertSame(1, Contrat::query()
            ->where('type', TypeContrat::Cdd)
            ->where('statut', StatutContrat::Cree)
            ->whereNull('contrat_precedent_id')
            ->count());
        $this->assertSame(1, Contrat::query()
            ->where('type', TypeContrat::Cdi)
            ->where('statut', StatutContrat::Signe)
            ->count());
        $this->assertSame(1, Contrat::query()
            ->where('type', TypeContrat::Cdi)
            ->where('statut', StatutContrat::Valide)
            ->count());
        $this->assertSame(1, Contrat::query()
            ->where('type', TypeContrat::Renouvellement)
            ->where('statut', StatutContrat::Cree)
            ->whereNotNull('contrat_precedent_id')
            ->count());
        $this->assertSame(1, Onboarding::query()
            ->where('statut', StatutOnboarding::Termine)
            ->count());

        foreach (Contrat::query()->orderBy('id')->get() as $contrat) {
            $historique = DB::table('contrat_contrat_historiques')
                ->where('contrat_id', $contrat->getKey())
                ->orderBy('id')
                ->get(['ancien_statut', 'nouveau_statut'])
                ->map(fn (object $ligne): array => [$ligne->ancien_statut, $ligne->nouveau_statut])
                ->all();
            $attendus = match ($contrat->statut) {
                StatutContrat::Cree => [[null, StatutContrat::Cree->value]],
                StatutContrat::Signe => [
                    [null, StatutContrat::Cree->value],
                    [StatutContrat::Cree->value, StatutContrat::Signe->value],
                ],
                StatutContrat::Valide => [
                    [null, StatutContrat::Cree->value],
                    [StatutContrat::Cree->value, StatutContrat::Signe->value],
                    [StatutContrat::Signe->value, StatutContrat::Valide->value],
                ],
            };

            $this->assertSame($attendus, $historique);
        }

        $matricules = Employe::query()->whereNotNull('matricule')->pluck('matricule');
        $this->assertSame(4, $matricules->count());
        $this->assertSame($matricules->count(), $matricules->unique()->count());

        $rh = User::query()->where('email', 'rh@sirh.test')->firstOrFail();
        $manager = User::query()->where('email', 'manager@sirh.test')->firstOrFail();
        $finance = User::query()->where('email', 'finance@sirh.test')->firstOrFail();

        $this->assertSame(RoleUtilisateur::Rh, $rh->role);
        $this->assertSame(RoleUtilisateur::Manager, $manager->role);
        $this->assertSame(RoleUtilisateur::Finance, $finance->role);

        $this->actingAs($rh)->get(ContratResource::getUrl('index'))->assertOk();
        $this->assertTrue((new OnboardingPolicy)->viewAny($manager));
        $this->assertTrue((new ContratPolicy)->viewAny($finance));
    }
}
