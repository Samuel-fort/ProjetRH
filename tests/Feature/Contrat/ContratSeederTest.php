<?php

namespace Tests\Feature\Contrat;

use App\Enums\StatutContrat;
use App\Enums\StatutOnboarding;
use App\Enums\StatutTache;
use App\Enums\TypeContrat;
use App\Enums\TypeTacheOnboarding;
use App\Models\Contrat\Affectation;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Document;
use App\Models\Contrat\Onboarding;
use App\Models\Embauche\Employe;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\ContratSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContratSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_seeder_cree_les_cinq_scenarios_et_reste_idempotent(): void
    {
        Notification::fake();

        $this->seed(ContratSeeder::class);

        $this->assertSame(5, Employe::query()->count());
        $this->assertSame(6, Contrat::query()->count());
        $this->assertSame(3, Onboarding::query()->count());
        $this->assertSame(13, Contrat::query()->withCount('historiques')->get()->sum('historiques_count'));
        $this->assertSame(2, Document::query()->count());
        $this->assertHistoriquesEtMatriculesCoherents();

        $contratCree = Contrat::query()
            ->where('type', TypeContrat::Cdd)
            ->where('statut', StatutContrat::Cree)
            ->whereNull('contrat_precedent_id')
            ->firstOrFail();
        $this->assertNull($contratCree->employe->matricule);
        $this->assertSame(1, Affectation::query()->where('employe_id', $contratCree->employe_id)->count());
        $this->assertSame([null, StatutContrat::Cree], [
            $contratCree->historiques()->oldest('id')->firstOrFail()->ancien_statut,
            $contratCree->historiques()->oldest('id')->firstOrFail()->nouveau_statut,
        ]);

        $contratSigne = Contrat::query()
            ->where('type', TypeContrat::Cdi)
            ->where('statut', StatutContrat::Signe)
            ->firstOrFail();
        $this->assertNotNull($contratSigne->employe->matricule);
        $this->assertSame(0, Onboarding::query()->where('contrat_id', $contratSigne->getKey())->count());

        $contratCdiValide = Contrat::query()
            ->where('type', TypeContrat::Cdi)
            ->where('statut', StatutContrat::Valide)
            ->firstOrFail();
        $onboardingCdi = Onboarding::query()->where('contrat_id', $contratCdiValide->getKey())->firstOrFail();
        $this->assertSame(6, $onboardingCdi->taches()->count());
        $this->assertSame(3, $onboardingCdi->taches()->where('statut', StatutTache::Fait)->count());
        $tacheEnRetard = $onboardingCdi->taches()->where('code', TypeTacheOnboarding::Materiel)->firstOrFail();
        $this->assertTrue($tacheEnRetard->date_echeance->lt(CarbonImmutable::today()));
        $this->assertSame(
            User::query()->first()?->getKey(),
            $tacheEnRetard->responsable_id
        );

        $contratRenouvellement = Contrat::query()
            ->where('type', TypeContrat::Renouvellement)
            ->firstOrFail();
        $contratSource = $contratRenouvellement->precedent;
        $this->assertSame(StatutContrat::Cree, $contratRenouvellement->statut);
        $this->assertSame(StatutContrat::Valide, $contratSource->statut);
        $this->assertSame(TypeContrat::Cdd, $contratSource->type);
        $attributsSource = $contratSource->getAttributes();

        $onboardingTermine = Onboarding::query()
            ->where('statut', StatutOnboarding::Termine)
            ->firstOrFail();
        $this->assertSame(6, $onboardingTermine->taches()->where('statut', StatutTache::Fait)->count());
        $affectations = Affectation::query()
            ->where('employe_id', $onboardingTermine->employe_id)
            ->orderBy('date_debut')
            ->get();
        $this->assertCount(2, $affectations);
        $this->assertNotNull($affectations[0]->date_fin);
        $this->assertNull($affectations[1]->date_fin);

        $this->assertSame(1, Document::query()->where('documentable_type', $contratSigne->getMorphClass())->count());
        $this->assertSame(1, Document::query()->where('documentable_type', $contratCree->employe->getMorphClass())->count());

        $comptesAvantRelance = $this->comptesTablesBloc4();

        $this->seed(ContratSeeder::class);

        $this->assertSame($comptesAvantRelance, $this->comptesTablesBloc4());
        $this->assertSame($attributsSource, $contratSource->fresh()->getAttributes());
    }

    public function test_le_seeder_produit_les_memes_historiques_sans_evenements_et_reste_idempotent(): void
    {
        Notification::fake();

        Model::withoutEvents(fn () => $this->seed(ContratSeeder::class));

        $this->assertHistoriquesEtMatriculesCoherents();

        $comptesAvantRelance = $this->comptesTablesBloc4();

        $this->seed(ContratSeeder::class);

        $this->assertSame($comptesAvantRelance, $this->comptesTablesBloc4());
    }

    private function assertHistoriquesEtMatriculesCoherents(): void
    {
        foreach (Contrat::query()->orderBy('id')->get() as $contrat) {
            $statutsAttendus = match ($contrat->statut) {
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
            $historique = DB::table('contrat_contrat_historiques')
                ->where('contrat_id', $contrat->getKey())
                ->orderBy('id')
                ->get()
                ->map(fn (object $ligne): array => [$ligne->ancien_statut, $ligne->nouveau_statut])
                ->all();

            $this->assertSame($statutsAttendus, $historique);
        }

        $matricules = Employe::query()
            ->whereNotNull('matricule')
            ->pluck('matricule');

        $this->assertCount(4, $matricules);
        $this->assertSame($matricules->count(), $matricules->unique()->count());
    }

    /**
     * @return array<string, int>
     */
    private function comptesTablesBloc4(): array
    {
        $tables = [
            'contrat_contrats',
            'contrat_affectations',
            'contrat_onboardings',
            'contrat_onboarding_taches',
            'contrat_documents',
            'contrat_contrat_historiques',
            'contrat_compteur_matricules',
        ];

        return array_combine(
            $tables,
            array_map(static fn (string $table): int => DB::table($table)->count(), $tables)
        );
    }
}
