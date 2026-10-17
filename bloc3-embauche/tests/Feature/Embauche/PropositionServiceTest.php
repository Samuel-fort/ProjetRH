<?php

namespace Tests\Feature\Embauche;

use App\Enums\StatutCandidature;
use App\Enums\StatutEmploye;
use App\Enums\StatutProposition;
use App\Events\PropositionAcceptee;
use App\Listeners\GenererContratPdfApresAcceptation;
use App\Models\Embauche\Employe;
use App\Models\Embauche\PropositionEmbauche;
use App\Models\Recrutement\Candidat;
use App\Models\Recrutement\Candidature;
use App\Services\Embauche\GenererContratPdf;
use App\Services\Embauche\PropositionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** Tests du Bloc 3 : de la candidature retenue à l'employé. */
class PropositionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PropositionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PropositionService::class);
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'salaire' => 1500000,
            'horaires' => 'Lundi-vendredi, 8h-17h',
            'lieu_travail' => 'Antananarivo',
            'date_debut' => now()->addDays(15)->toDateString(),
        ], $surcharge);
    }

    private function retenue(): Candidature
    {
        return Candidature::factory()->statut(StatutCandidature::Retenue)->create();
    }

    public function test_impossible_de_faire_une_proposition_pour_une_candidature_non_retenue(): void
    {
        foreach ([StatutCandidature::Recue, StatutCandidature::Preselectionnee, StatutCandidature::EnEntretien,
            StatutCandidature::EnTest, StatutCandidature::Refusee] as $statut) {
            $candidature = Candidature::factory()->statut($statut)->create();

            try {
                $this->service->creer($candidature, $this->donnees());
                $this->fail("Proposition créée pour une candidature « {$statut->value} ».");
            } catch (DomainException) {
                $this->assertSame(0, PropositionEmbauche::count());
            }
        }
    }

    public function test_une_seule_proposition_en_cours_par_candidature(): void
    {
        $candidature = $this->retenue();
        $this->service->creer($candidature, $this->donnees());

        $this->expectException(DomainException::class);
        $this->service->creer($candidature, $this->donnees());
    }

    public function test_une_nouvelle_proposition_est_possible_apres_un_refus(): void
    {
        $candidature = $this->retenue();
        $p = $this->service->envoyer($this->service->creer($candidature, $this->donnees()));
        $this->service->refuser($p);

        $this->assertSame(StatutProposition::Refusee, $p->fresh()->statut);
        $this->assertNotNull($this->service->creer($candidature, $this->donnees())->id);
    }

    public function test_validation_du_salaire_et_de_la_date(): void
    {
        $candidature = $this->retenue();

        foreach ([['salaire' => 0], ['date_debut' => now()->subDay()->toDateString()]] as $mauvais) {
            try {
                $this->service->creer($candidature, $this->donnees($mauvais));
                $this->fail('Une donnée invalide a été acceptée.');
            } catch (DomainException) {
                $this->assertSame(0, PropositionEmbauche::count());
            }
        }
    }

    public function test_l_acceptation_cree_un_employe_lie_au_bon_candidat_avec_son_matricule(): void
    {
        Event::fake([PropositionAcceptee::class]);
        $candidature = $this->retenue();
        $p = $this->service->envoyer($this->service->creer($candidature, $this->donnees()));

        $this->service->accepter($p);

        $employe = Employe::sole();
        $this->assertSame($candidature->candidat_id, $employe->candidat_id);
        $this->assertSame($candidature->candidat->nom, $employe->nom);
        $this->assertSame($candidature->candidat->email, $employe->email);
        $this->assertSame(StatutEmploye::EnIntegration, $employe->statut);
        $this->assertSame('EMP-'.now()->year.'-0001', $employe->matricule);

        $p->refresh();
        $this->assertSame(StatutProposition::Acceptee, $p->statut);
        $this->assertSame($employe->id, $p->employe_id);
        $this->assertNotNull($p->date_reponse);

        Event::assertDispatched(PropositionAcceptee::class, fn ($e) => $e->proposition->is($p));
    }

    public function test_le_refus_ne_cree_ni_employe_ni_evenement(): void
    {
        Event::fake([PropositionAcceptee::class]);
        $p = $this->service->envoyer($this->service->creer($this->retenue(), $this->donnees()));

        $this->service->refuser($p);

        $this->assertSame(0, Employe::count());
        Event::assertNotDispatched(PropositionAcceptee::class);
        $this->assertNotNull($p->fresh()->date_reponse);
    }

    public function test_les_transitions_de_statut_sont_controlees(): void
    {
        $p = $this->service->creer($this->retenue(), $this->donnees());

        // Un brouillon ne peut être ni accepté ni refusé : il doit d'abord être envoyé.
        foreach (['accepter', 'refuser'] as $methode) {
            try {
                $this->service->$methode($p);
                $this->fail("$methode possible sur un brouillon");
            } catch (DomainException) {
                $this->assertSame(StatutProposition::Brouillon, $p->fresh()->statut);
            }
        }

        // Une proposition acceptée ne peut plus être acceptée à nouveau (pas de 2e employé).
        $this->service->envoyer($p);
        $this->service->accepter($p);
        $this->expectException(DomainException::class);
        $this->service->accepter($p);
    }

    public function test_une_personne_reste_un_seul_employe_en_cas_de_reembauche(): void
    {
        $candidat = Candidat::factory()->create();
        $matricules = [];

        foreach ([1, 2] as $_) {
            $candidature = Candidature::factory()->statut(StatutCandidature::Retenue)->create(['candidat_id' => $candidat->id]);
            $p = $this->service->envoyer($this->service->creer($candidature, $this->donnees()));
            $matricules[] = $this->service->accepter($p)->employe->matricule;
        }

        $this->assertSame(1, Employe::count());
        $this->assertSame($matricules[0], $matricules[1]); // le matricule ne change jamais
    }

    public function test_l_ecouteur_demande_la_generation_du_pdf(): void
    {
        // On remplace le générateur par un faux pour vérifier seulement l'appel.
        $appels = [];
        $this->app->instance(GenererContratPdf::class, new class($appels) extends GenererContratPdf {
            public function __construct(public array &$appels) {}
            public function depuisProposition(PropositionEmbauche $p): string { $this->appels[] = $p->id; return 'x.pdf'; }
        });

        $p = $this->service->envoyer($this->service->creer($this->retenue(), $this->donnees()));
        $this->service->accepter($p); // vrai événement : l'écouteur est détecté automatiquement

        $this->assertSame([$p->id], $appels);
    }

    public function test_une_erreur_de_pdf_n_annule_pas_l_acceptation(): void
    {
        $this->app->instance(GenererContratPdf::class, new class extends GenererContratPdf {
            public function depuisProposition(PropositionEmbauche $p): string { throw new \RuntimeException('PDF KO'); }
        });
        $this->withoutExceptionHandling(); // report() ne doit pas faire planter le test

        $p = $this->service->envoyer($this->service->creer($this->retenue(), $this->donnees()));
        $this->service->accepter($p);

        $this->assertSame(StatutProposition::Acceptee, $p->fresh()->statut);
        $this->assertSame(1, Employe::count());
    }
}
