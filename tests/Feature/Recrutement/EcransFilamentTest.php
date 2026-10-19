<?php

namespace Tests\Feature\Recrutement;

use App\Enums\StatutCandidature;
use App\Enums\StatutOffre;
use App\Filament\Resources\Recrutement\Candidatures\Pages\ListCandidatures;
use App\Filament\Resources\Recrutement\Candidatures\Pages\ViewCandidature;
use App\Filament\Resources\Recrutement\Candidats\Pages\CreateCandidat;
use App\Filament\Resources\Recrutement\OffreEmplois\Pages\CreateOffreEmploi;
use App\Filament\Resources\Recrutement\OffreEmplois\Pages\EditOffreEmploi;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Recrutement\Candidat;
use App\Models\Recrutement\Candidature;
use App\Models\Recrutement\OffreEmploi;
use App\Models\User;
use Database\Seeders\RecrutementSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Tests des écrans Filament du Bloc 2 (on simule un utilisateur qui clique). */
class EcransFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function connecte(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);

        return $user;
    }

    public function test_le_seeder_remplit_le_bloc_sans_doublon_de_candidat(): void
    {
        $this->seed(RecrutementSeeder::class);
        $this->seed(RecrutementSeeder::class); // relancé : ne doit rien dupliquer

        $this->assertSame(3, OffreEmploi::count());
        $this->assertSame(5, Candidat::count());       // 6 candidatures mais 5 personnes
        $this->assertSame(6, Candidature::count());
        $this->assertSame(1, Candidature::where('statut', StatutCandidature::Retenue)->count());
    }

    public function test_la_rh_cree_puis_publie_une_offre_depuis_une_demande_validee(): void
    {
        $this->connecte('rh');
        $demande = DemandeRecrutement::factory()->create(['statut' => 'recrutement_ouvert']);

        Livewire::test(CreateOffreEmploi::class)
            ->fillForm(['demande_recrutement_id' => $demande->id, 'titre' => 'Développeur', 'description' => 'Desc'])
            ->call('create')
            ->assertHasNoFormErrors();

        $offre = OffreEmploi::firstWhere('titre', 'Développeur');
        $this->assertSame(StatutOffre::Brouillon, $offre->statut);

        Livewire::test(EditOffreEmploi::class, ['record' => $offre->id])
            ->callAction('publier');
        $this->assertSame(StatutOffre::Publiee, $offre->fresh()->statut);
    }

    public function test_une_demande_non_validee_n_est_pas_acceptee_dans_le_formulaire(): void
    {
        $this->connecte('rh');
        $demande = DemandeRecrutement::factory()->create(['statut' => 'en_validation']);

        Livewire::test(CreateOffreEmploi::class)
            ->fillForm(['demande_recrutement_id' => $demande->id, 'titre' => 'X'])
            ->call('create')
            ->assertHasFormErrors(['demande_recrutement_id']);

        $this->assertSame(0, OffreEmploi::count());
    }

    public function test_un_manager_ne_peut_pas_creer_d_offre(): void
    {
        $this->connecte('manager');

        Livewire::test(CreateOffreEmploi::class)->assertForbidden();
    }

    public function test_enregistrer_une_candidature_depuis_la_liste(): void
    {
        $this->connecte('rh');
        $offre = OffreEmploi::factory()->publiee()->create();

        Livewire::test(ListCandidatures::class)
            ->callAction('recevoir', [
                'offre_emploi_id' => $offre->id, 'nom' => 'Rabe', 'prenom' => 'Paul', 'email' => 'paul@exemple.mg',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(1, Candidature::count());
    }

    public function test_pipeline_complet_par_les_boutons(): void
    {
        $rh = $this->connecte('rh');
        $candidature = Candidature::factory()->create();

        $page = Livewire::test(ViewCandidature::class, ['record' => $candidature->id]);

        // Au départ ("reçue") on ne peut ni retenir ni refuser sans présélection ; on peut refuser.
        $page->assertActionHidden('retenir')->assertActionVisible('preselectionner');

        $page->callAction('preselectionner')
            ->callAction('planifier', ['type' => 'entretien', 'date_heure' => now()->addDay()->toDateTimeString(), 'lieu' => 'RH'])
            ->callAction('evaluer', ['note' => 14, 'commentaire' => 'ok'])
            ->callAction('retenir');

        $candidature->refresh();
        $this->assertSame(StatutCandidature::Retenue, $candidature->statut);
        $this->assertCount(1, $candidature->entretiens);
        $this->assertCount(1, $candidature->evaluations);
        $this->assertSame(
            [StatutCandidature::Recue, StatutCandidature::Preselectionnee, StatutCandidature::EnEntretien, StatutCandidature::Retenue],
            $candidature->historiques()->oldest('id')->get()->pluck('nouveau_statut')->all()
        );
        $this->assertSame($rh->id, $candidature->historiques()->oldest('id')->get()[1]->user_id);

        // Décision finale : plus aucun bouton de décision.
        Livewire::test(ViewCandidature::class, ['record' => $candidature->id])
            ->assertActionHidden('retenir')->assertActionHidden('refuser')->assertActionHidden('planifier');
    }

    public function test_un_manager_peut_evaluer_mais_pas_decider(): void
    {
        $this->connecte('manager');
        $candidature = Candidature::factory()->statut(StatutCandidature::EnEntretien)->create();

        Livewire::test(ViewCandidature::class, ['record' => $candidature->id])
            ->assertActionVisible('evaluer')
            ->assertActionHidden('retenir')
            ->assertActionHidden('refuser');
    }

    public function test_creation_d_un_candidat_refuse_un_email_deja_utilise(): void
    {
        $this->connecte('rh');
        Candidat::factory()->create(['email' => 'deja@exemple.mg']);

        Livewire::test(CreateCandidat::class)
            ->fillForm(['nom' => 'A', 'prenom' => 'B', 'email' => 'deja@exemple.mg'])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }
}
