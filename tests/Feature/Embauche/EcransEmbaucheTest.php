<?php

namespace Tests\Feature\Embauche;

use App\Enums\StatutCandidature;
use App\Enums\StatutProposition;
use App\Filament\Resources\Embauche\Employes\Pages\EditEmploye;
use App\Filament\Resources\Embauche\Employes\Pages\ListEmployes;
use App\Filament\Resources\Embauche\PropositionEmbauches\Pages\CreatePropositionEmbauche;
use App\Filament\Resources\Embauche\PropositionEmbauches\Pages\EditPropositionEmbauche;
use App\Filament\Resources\Embauche\PropositionEmbauches\Pages\ListPropositionEmbauches;
use App\Models\Embauche\Employe;
use App\Models\Embauche\PropositionEmbauche;
use App\Models\Recrutement\Candidature;
use App\Models\User;
use App\Services\Embauche\GenererContratPdf;
use Database\Seeders\EmbaucheSeeder;
use Database\Seeders\RecrutementSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Tests des écrans Filament du Bloc 3 (simulation de clics). */
class EcransEmbaucheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // Ces tests ne vérifient pas dompdf : on neutralise la conversion PDF.
        $this->app->instance(GenererContratPdf::class, new class extends GenererContratPdf
        {
            protected function rendrePdf(string $html): string
            {
                return '%PDF-1.4 faux';
            }
        });
    }

    private function connecte(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);

        return $user;
    }

    public function test_la_rh_cree_envoie_puis_enregistre_l_acceptation(): void
    {
        $this->connecte('rh');
        $candidature = Candidature::factory()->statut(StatutCandidature::Retenue)->create();

        Livewire::test(CreatePropositionEmbauche::class)
            ->fillForm([
                'candidature_id' => $candidature->id, 'salaire' => 1500000, 'horaires' => '8h-17h',
                'lieu_travail' => 'Tana', 'date_debut' => now()->addDays(10)->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $proposition = PropositionEmbauche::sole();
        $this->assertSame(StatutProposition::Brouillon, $proposition->statut);

        $page = Livewire::test(EditPropositionEmbauche::class, ['record' => $proposition->id]);
        $page->assertActionHidden('accepter')->callAction('envoyer')->assertActionHidden('envoyer');
        $this->assertSame(StatutProposition::Envoyee, $proposition->fresh()->statut);

        $page->callAction('accepter');

        $proposition->refresh();
        $this->assertSame(StatutProposition::Acceptee, $proposition->statut);
        $this->assertSame($candidature->candidat_id, Employe::sole()->candidat_id);
        $this->assertNotNull(Employe::sole()->matricule);
        $this->assertNotNull($proposition->contrat_pdf_path); // PDF généré par l'écouteur
    }

    public function test_seules_les_candidatures_retenues_sont_proposees(): void
    {
        $this->connecte('rh');
        $enEntretien = Candidature::factory()->statut(StatutCandidature::EnEntretien)->create();

        Livewire::test(CreatePropositionEmbauche::class)
            ->fillForm(['candidature_id' => $enEntretien->id, 'salaire' => 1000000, 'horaires' => 'x',
                'lieu_travail' => 'x', 'date_debut' => now()->addDay()->toDateString()])
            ->call('create')
            ->assertHasFormErrors(['candidature_id']);

        $this->assertSame(0, PropositionEmbauche::count());
    }

    public function test_une_proposition_envoyee_est_verrouillee_et_se_refuse(): void
    {
        $this->connecte('rh');
        $p = PropositionEmbauche::factory()->statut(StatutProposition::Envoyee)->create();

        Livewire::test(EditPropositionEmbauche::class, ['record' => $p->id])
            ->assertFormFieldIsDisabled('salaire')
            ->callAction('refuser');

        $this->assertSame(StatutProposition::Refusee, $p->fresh()->statut);
        $this->assertSame(0, Employe::count());
    }

    public function test_les_droits_dependent_du_role(): void
    {
        $p = PropositionEmbauche::factory()->statut(StatutProposition::Envoyee)->create();

        // La direction peut préparer/envoyer mais pas enregistrer la réponse du candidat.
        $this->connecte('direction');
        Livewire::test(EditPropositionEmbauche::class, ['record' => $p->id])->assertActionHidden('accepter');

        // La finance consulte seulement.
        $this->connecte('finance');
        Livewire::test(ListPropositionEmbauches::class)->assertSuccessful();
        Livewire::test(CreatePropositionEmbauche::class)->assertForbidden();

        // Un manager n'a aucun accès aux propositions (salaires) ni aux employés.
        $this->connecte('manager');
        Livewire::test(ListPropositionEmbauches::class)->assertForbidden();
        Livewire::test(ListEmployes::class)->assertForbidden();
    }

    public function test_un_matricule_s_attribue_a_un_employe_importe(): void
    {
        $this->connecte('rh');
        $employe = Employe::factory()->create();

        Livewire::test(EditEmploye::class, ['record' => $employe->id])->callAction('matricule');

        $this->assertSame('EMP-'.now()->year.'-0001', $employe->fresh()->matricule);
        Livewire::test(EditEmploye::class, ['record' => $employe->id])->assertActionHidden('matricule');
    }

    public function test_les_listes_s_affichent(): void
    {
        $this->connecte('rh');
        $employe = Employe::factory()->create(['matricule' => 'EMP-2026-0007']);
        $p = PropositionEmbauche::factory()->create();

        Livewire::test(ListEmployes::class)->assertCanSeeTableRecords([$employe])->searchTable('EMP-2026-0007');
        Livewire::test(ListPropositionEmbauches::class)->assertCanSeeTableRecords([$p])->filterTable('statut', 'brouillon');
    }

    public function test_les_seeders_enchaines_sont_coherents_et_relancables(): void
    {
        foreach (range(1, 2) as $_) {            // 2 passages : aucune duplication
            $this->seed(RecrutementSeeder::class);
            $this->seed(EmbaucheSeeder::class);
        }

        $this->assertSame(2, Employe::count());                       // 1 issu d'un candidat + 1 importé
        $this->assertSame(1, PropositionEmbauche::count());
        $this->assertSame(StatutProposition::Acceptee, PropositionEmbauche::sole()->statut);
        $this->assertSame(2, Employe::whereNotNull('matricule')->distinct('matricule')->count('matricule'));
        $this->assertSame('Rakoto', Employe::whereNotNull('candidat_id')->sole()->nom); // Jean Rakoto, retenu au Bloc 2
    }
}
