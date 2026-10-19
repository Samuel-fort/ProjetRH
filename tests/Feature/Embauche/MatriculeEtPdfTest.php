<?php

namespace Tests\Feature\Embauche;

use App\Models\Embauche\Employe;
use App\Models\Embauche\PropositionEmbauche;
use App\Services\Embauche\GenererContratPdf;
use App\Services\Embauche\MatriculeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MatriculeEtPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_matricules_se_suivent_et_ne_changent_pas(): void
    {
        $service = app(MatriculeService::class);
        $annee = now()->year;

        $a = $service->attribuer(Employe::factory()->create());
        $b = $service->attribuer(Employe::factory()->create());

        $this->assertSame("EMP-{$annee}-0001", $a->matricule);
        $this->assertSame("EMP-{$annee}-0002", $b->matricule);

        $service->attribuer($a); // déjà attribué : inchangé
        $this->assertSame("EMP-{$annee}-0001", $a->fresh()->matricule);
    }

    public function test_la_numerotation_repart_a_zero_chaque_annee(): void
    {
        Employe::factory()->create(['matricule' => 'EMP-2020-0042']);

        $e = app(MatriculeService::class)->attribuer(Employe::factory()->create());

        $this->assertSame('EMP-'.now()->year.'-0001', $e->matricule);
    }

    public function test_le_numero_passe_correctement_les_10000(): void
    {
        $annee = now()->year;
        Employe::factory()->create(['matricule' => "EMP-{$annee}-9999"]);

        $e = app(MatriculeService::class)->attribuer(Employe::factory()->create());

        $this->assertSame("EMP-{$annee}-10000", $e->matricule);
    }

    public function test_le_gabarit_du_contrat_affiche_les_donnees_de_la_proposition(): void
    {
        $p = PropositionEmbauche::factory()->create(['salaire' => 1500000, 'horaires' => '8h-17h', 'lieu_travail' => 'Toamasina']);

        $d = app(GenererContratPdf::class)->donnees($p);
        $html = view('pdf.contrat', ['d' => $d])->render();

        $this->assertStringContainsString($p->candidature->candidat->nom_complet, $html);
        $this->assertStringContainsString($p->candidature->offreEmploi->titre, $html);
        $this->assertStringContainsString('1 500 000 Ar', $html);
        $this->assertStringContainsString('Toamasina', $html);
        $this->assertStringContainsString($p->date_debut->format('d/m/Y'), $html);
    }

    public function test_le_pdf_est_enregistre_et_son_chemin_memorise(): void
    {
        Storage::fake('local');
        $p = PropositionEmbauche::factory()->create();

        // On remplace uniquement la conversion HTML -> PDF (dompdf n'est pas nécessaire pour ce test).
        $generateur = new class extends GenererContratPdf
        {
            protected function rendrePdf(string $html): string
            {
                return '%PDF-1.4 faux';
            }
        };
        $chemin = $generateur->depuisProposition($p);

        Storage::disk('local')->assertExists($chemin);
        $this->assertSame($chemin, $p->fresh()->contrat_pdf_path);
    }

    public function test_pdf_reel_avec_dompdf(): void
    {
        if (! class_exists(Pdf::class)) {
            $this->markTestSkipped('barryvdh/laravel-dompdf non installé : composer require barryvdh/laravel-dompdf');
        }
        Storage::fake('local');
        $p = PropositionEmbauche::factory()->create();

        $chemin = app(GenererContratPdf::class)->depuisProposition($p);

        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($chemin));
    }
}
