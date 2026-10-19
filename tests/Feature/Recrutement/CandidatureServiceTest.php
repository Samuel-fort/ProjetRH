<?php

namespace Tests\Feature\Recrutement;

use App\Enums\StatutCandidature;
use App\Enums\TypeEtape;
use App\Models\Recrutement\Candidat;
use App\Models\Recrutement\Candidature;
use App\Models\Recrutement\OffreEmploi;
use App\Models\User;
use App\Services\Recrutement\CandidatureService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tests du Bloc 2 : pipeline de candidature, doublons, historique. */
class CandidatureServiceTest extends TestCase
{
    use RefreshDatabase;

    private CandidatureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CandidatureService::class);
    }

    private function infos(string $email = 'rakoto@exemple.mg'): array
    {
        return ['nom' => 'Rakoto', 'prenom' => 'Jean', 'email' => $email];
    }

    public function test_un_candidat_avec_deux_candidatures_reste_un_seul_candidat(): void
    {
        $offre1 = OffreEmploi::factory()->publiee()->create();
        $offre2 = OffreEmploi::factory()->publiee()->create();

        $c1 = $this->service->recevoirCandidature($offre1, $this->infos());
        // Même personne, email écrit différemment (majuscules + espace) -> toujours le même candidat.
        $c2 = $this->service->recevoirCandidature($offre2, $this->infos('  Rakoto@Exemple.mg '));

        $this->assertSame($c1->candidat_id, $c2->candidat_id);
        $this->assertSame(1, Candidat::count());
        $this->assertSame(2, Candidature::count());
    }

    public function test_postuler_deux_fois_a_la_meme_offre_est_refuse(): void
    {
        $offre = OffreEmploi::factory()->publiee()->create();
        $this->service->recevoirCandidature($offre, $this->infos());

        $this->expectException(DomainException::class);
        $this->service->recevoirCandidature($offre, $this->infos());
    }

    public function test_une_offre_non_publiee_refuse_les_candidatures(): void
    {
        $brouillon = OffreEmploi::factory()->create(); // statut brouillon par défaut

        $this->expectException(DomainException::class);
        $this->service->recevoirCandidature($brouillon, $this->infos());
    }

    public function test_les_changements_de_statut_sont_journalises_avec_l_utilisateur(): void
    {
        $rh = User::factory()->create(['role' => 'rh']);
        $this->actingAs($rh);

        $candidature = $this->service->recevoirCandidature(OffreEmploi::factory()->publiee()->create(), $this->infos());
        $this->service->preselectionner($candidature);
        $this->service->retenir($this->service->changerStatut($candidature, StatutCandidature::EnEntretien));

        $historique = $candidature->historiques()->oldest('id')->get();
        $this->assertSame(
            [null, 'recue', 'preselectionnee', 'en_entretien'],
            $historique->map(fn ($h) => $h->ancien_statut?->value)->all()
        );
        $this->assertSame(
            ['recue', 'preselectionnee', 'en_entretien', 'retenue'],
            $historique->map(fn ($h) => $h->nouveau_statut->value)->all()
        );
        $this->assertSame($rh->id, $historique->first()->user_id);
    }

    public function test_les_transitions_interdites_sont_refusees(): void
    {
        $candidature = Candidature::factory()->create(); // statut "recue"

        // On ne retient pas quelqu'un sans passer par la sélection.
        $this->expectException(DomainException::class);
        $this->service->retenir($candidature);
    }

    public function test_une_decision_finale_est_definitive(): void
    {
        $candidature = Candidature::factory()->statut(StatutCandidature::Refusee)->create();

        $this->expectException(DomainException::class);
        $this->service->changerStatut($candidature, StatutCandidature::Preselectionnee);
    }

    public function test_planifier_un_entretien_fait_avancer_la_candidature(): void
    {
        $candidature = Candidature::factory()->statut(StatutCandidature::Preselectionnee)->create();

        $this->service->planifierEntretien($candidature, TypeEtape::Entretien, now()->addDays(2), 'Bureau RH');

        $this->assertSame(StatutCandidature::EnEntretien, $candidature->fresh()->statut);
        $this->assertCount(1, $candidature->entretiens);
    }

    public function test_pas_d_entretien_avant_la_preselection(): void
    {
        $candidature = Candidature::factory()->create(); // "recue"

        $this->expectException(DomainException::class);
        $this->service->planifierEntretien($candidature, TypeEtape::Entretien, now()->addDay());
    }

    public function test_evaluation_note_entre_0_et_20(): void
    {
        $candidature = Candidature::factory()->create();
        $evaluateur = User::factory()->create();

        $eval = $this->service->enregistrerEvaluation($candidature, $evaluateur, 15.5, 'Très bon profil');
        $this->assertEquals(15.5, $eval->fresh()->note);

        $this->expectException(DomainException::class);
        $this->service->enregistrerEvaluation($candidature, $evaluateur, 25);
    }
}
