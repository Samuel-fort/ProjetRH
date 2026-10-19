<?php

namespace Tests\Feature\Contrat;

use App\Enums\StatutContrat;
use App\Enums\StatutOnboarding;
use App\Enums\StatutTache;
use App\Enums\TypeContrat;
use App\Enums\TypeDocument;
use App\Enums\TypeTacheOnboarding;
use App\Models\Besoin\Departement;
use App\Models\Besoin\Poste;
use App\Models\Contrat\Affectation;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\ContratHistorique;
use App\Models\Contrat\Document;
use App\Models\Contrat\Onboarding;
use App\Models\Embauche\Employe;
use App\Models\User;
use App\Notifications\Contrat\TacheOnboardingAssignee;
use App\Services\Contrat\AffectationService;
use App\Services\Contrat\ContratService;
use App\Services\Contrat\DocumentService;
use App\Services\Contrat\OnboardingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParcoursCompletContratTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_parcours_complet_de_contrat_affectation_document_et_onboarding(): void
    {
        Storage::fake('local');
        Notification::fake();

        $aujourdHui = CarbonImmutable::today();
        $employe = Employe::factory()->create();
        $responsable = User::factory()->create();
        $departement = Departement::query()->create([
            'nom' => 'Département parcours complet',
            'code' => 'DEP-PARCOURS',
        ]);
        $poste = Poste::query()->create([
            'intitule' => 'Poste parcours complet',
            'description' => 'Poste de test de parcours',
            'departement_id' => $departement->getKey(),
        ]);
        $contratService = app(ContratService::class);
        $affectationService = app(AffectationService::class);
        $onboardingService = app(OnboardingService::class);
        $documentService = app(DocumentService::class);

        $contrat = $contratService->creer($employe, [
            'type' => TypeContrat::Cdd,
            'date_debut' => $aujourdHui,
            'date_fin' => $aujourdHui->addDay(),
            'salaire' => 3200,
            'horaires' => '08h00 - 17h00',
            'lieu_travail' => 'Antananarivo',
        ]);

        $this->assertSame(StatutContrat::Cree, $contrat->statut);
        $this->assertNull($employe->fresh()->matricule);
        $this->assertDatabaseCount('contrat_onboardings', 0);
        $this->assertSame([[null, StatutContrat::Cree->value]], $this->statutsHistorique($contrat));

        $affectationInitiale = $affectationService->affecter(
            $employe,
            $poste->getKey(),
            $departement->getKey(),
            $aujourdHui
        );
        $this->assertDatabaseCount('contrat_affectations', 1);
        $this->assertNull($affectationInitiale->fresh()->date_fin);

        $contrat = $contratService->signer($contrat, $aujourdHui);
        $this->assertSame(StatutContrat::Signe, $contrat->statut);
        $this->assertSame('EMP-'.$aujourdHui->format('Y').'-0001', $employe->fresh()->matricule);
        $this->assertSame([
            [null, StatutContrat::Cree->value],
            [StatutContrat::Cree->value, StatutContrat::Signe->value],
        ], $this->statutsHistorique($contrat));

        $contrat = $contratService->valider($contrat);
        $onboarding = Onboarding::query()->where('contrat_id', $contrat->getKey())->firstOrFail();
        $this->assertSame(StatutContrat::Valide, $contrat->statut);
        $this->assertSame(6, $onboarding->taches()->count());
        $this->assertSame(
            StatutTache::Fait,
            $onboarding->taches()->where('code', TypeTacheOnboarding::ContratSigne)->firstOrFail()->statut
        );
        $this->assertSame([
            [null, StatutContrat::Cree->value],
            [StatutContrat::Cree->value, StatutContrat::Signe->value],
            [StatutContrat::Signe->value, StatutContrat::Valide->value],
        ], $this->statutsHistorique($contrat));

        $documentContrat = $documentService->televerser(
            $contrat,
            UploadedFile::fake()->create('contrat-signe.pdf', 1, 'application/pdf'),
            TypeDocument::ContratSigne,
            null,
            $responsable
        );
        $documentEmploye = $documentService->televerser(
            $employe,
            UploadedFile::fake()->image('piece-identite.jpg'),
            TypeDocument::PieceIdentite,
            null,
            $responsable
        );
        $this->assertSame(2, Document::query()->count());
        $this->assertTrue(Storage::disk('local')->exists($documentContrat->chemin));
        $this->assertTrue(Storage::disk('local')->exists($documentEmploye->chemin));

        $tacheAssignee = $onboarding->taches()
            ->where('code', TypeTacheOnboarding::Badge)
            ->firstOrFail();
        $onboardingService->assignerTache($tacheAssignee, $responsable->getKey(), $aujourdHui);
        Notification::assertSentTo($responsable, TacheOnboardingAssignee::class);

        foreach ($onboarding->taches()->get() as $tache) {
            $onboardingService->changerStatutTache($tache, StatutTache::Fait);
        }

        $onboarding->refresh();
        $this->assertSame(StatutOnboarding::Termine, $onboarding->statut);
        $this->assertSame(6, $onboarding->taches()->where('statut', StatutTache::Fait)->count());

        $attributsSourceAvantRenouvellement = $contrat->fresh()->getAttributes();
        $nouveauContrat = $contratService->renouveler($contrat, [
            'date_debut' => $aujourdHui->addDays(2),
            'date_fin' => $aujourdHui->addYear()->addDays(2),
        ]);

        $this->assertSame(StatutContrat::Cree, $nouveauContrat->statut);
        $this->assertSame($contrat->getKey(), $nouveauContrat->contrat_precedent_id);
        $this->assertSame($attributsSourceAvantRenouvellement, $contrat->fresh()->getAttributes());
        $this->assertSame([[null, StatutContrat::Cree->value]], $this->statutsHistorique($nouveauContrat));
        $this->assertSame(3, $contrat->historiques()->count());
        $this->assertSame(1, $nouveauContrat->historiques()->count());

        $nouveauContrat = $contratService->signer($nouveauContrat, $aujourdHui->addDays(2));
        $this->assertSame('EMP-'.$aujourdHui->format('Y').'-0001', $employe->fresh()->matricule);
        $this->assertSame(2, $nouveauContrat->historiques()->count());
        $nouveauContrat = $contratService->valider($nouveauContrat);

        $this->assertSame(StatutContrat::Valide, $nouveauContrat->statut);
        $this->assertSame(1, Onboarding::query()->where('employe_id', $employe->getKey())->count());
        $this->assertSame(6, $onboarding->taches()->count());
        $this->assertSame([
            [null, StatutContrat::Cree->value],
            [StatutContrat::Cree->value, StatutContrat::Signe->value],
            [StatutContrat::Signe->value, StatutContrat::Valide->value],
        ], $this->statutsHistorique($nouveauContrat));

        $affectationSuivante = $affectationService->affecter(
            $employe,
            $poste->getKey(),
            $departement->getKey(),
            $aujourdHui->addDays(2)
        );
        $affectations = Affectation::query()
            ->where('employe_id', $employe->getKey())
            ->orderBy('date_debut')
            ->get();

        $this->assertCount(2, $affectations);
        $this->assertSame($aujourdHui->addDay()->toDateString(), $affectations[0]->date_fin->toDateString());
        $this->assertNull($affectationSuivante->fresh()->date_fin);
        $this->assertSame(1, Affectation::query()
            ->where('employe_id', $employe->getKey())
            ->whereNull('date_fin')
            ->count());
        $this->assertDatabaseCount('contrat_contrat_historiques', 6);
        $this->assertSame(3, $contrat->fresh()->historiques()->count());
        $this->assertSame(3, $nouveauContrat->fresh()->historiques()->count());
        $this->assertSame($attributsSourceAvantRenouvellement, $contrat->fresh()->getAttributes());
    }

    /**
     * @return array<int, array{0: ?string, 1: string}>
     */
    private function statutsHistorique(Contrat $contrat): array
    {
        return ContratHistorique::query()
            ->where('contrat_id', $contrat->getKey())
            ->orderBy('id')
            ->get()
            ->map(fn (ContratHistorique $historique): array => [
                $historique->ancien_statut?->value,
                $historique->nouveau_statut->value,
            ])
            ->all();
    }
}
