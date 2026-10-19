<?php

namespace Tests\Feature\Contrat;

use App\Enums\TypeDocument;
use App\Exceptions\Contrat\DocumentNonAutorise;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Document;
use App\Models\Embauche\Employe;
use App\Models\User;
use App\Services\Contrat\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_televerser_enregistre_un_document_de_contrat_sur_le_disque_prive(): void
    {
        Storage::fake('local');
        $contrat = Contrat::factory()->create();
        $fichier = UploadedFile::fake()->createWithContent('contrat.pdf', "%PDF-1.4\ncontenu du document");

        $document = app(DocumentService::class)->televerser($contrat, $fichier, TypeDocument::ContratSigne);

        $this->assertSame($contrat->getMorphClass(), $document->documentable_type);
        $this->assertSame($contrat->getKey(), $document->documentable_id);
        $this->assertSame(TypeDocument::ContratSigne, $document->type);
        Storage::disk('local')->assertExists($document->chemin);
    }

    public function test_televerser_enregistre_un_document_de_salarie_et_son_auteur(): void
    {
        Storage::fake('local');
        $employe = Employe::factory()->create();
        $auteur = User::factory()->create();
        $fichier = UploadedFile::fake()->image('piece.jpg');

        $document = app(DocumentService::class)->televerser(
            $employe,
            $fichier,
            TypeDocument::PieceIdentite,
            'Pièce identité',
            $auteur
        );

        $this->assertSame($employe->getMorphClass(), $document->documentable_type);
        $this->assertSame($auteur->getKey(), $document->televerse_par_id);
        Storage::disk('local')->assertExists($document->chemin);
    }

    public function test_une_cible_polymorphe_non_supportee_est_refusee(): void
    {
        $this->expectException(DocumentNonAutorise::class);

        app(DocumentService::class)->enregistrerChemin(
            User::factory()->create(),
            'fichier.pdf',
            TypeDocument::Autre
        );
    }

    public function test_une_extension_non_autorisee_est_refusee(): void
    {
        Storage::fake('local');
        $contrat = Contrat::factory()->create();
        $fichier = UploadedFile::fake()->createWithContent('script.txt', 'texte sans danger');

        $this->expectException(DocumentNonAutorise::class);

        app(DocumentService::class)->televerser($contrat, $fichier, TypeDocument::Autre);
    }

    public function test_un_fichier_de_plus_de_dix_mo_est_refuse(): void
    {
        Storage::fake('local');
        $contrat = Contrat::factory()->create();
        $contenu = "%PDF-1.4\n".str_repeat('a', 10 * 1024 * 1024);
        $fichier = UploadedFile::fake()->createWithContent('volumineux.pdf', $contenu);

        $this->expectException(DocumentNonAutorise::class);

        app(DocumentService::class)->televerser($contrat, $fichier, TypeDocument::Autre);
    }

    public function test_enregistrer_chemin_cree_la_ligne_sans_ecrire_de_fichier(): void
    {
        Storage::fake('local');
        $contrat = Contrat::factory()->create();

        $document = app(DocumentService::class)->enregistrerChemin(
            $contrat,
            'contrat/pdf/contrat-genere.pdf',
            TypeDocument::ContratGenere
        );

        $this->assertModelExists($document);
        $this->assertSame('contrat/pdf/contrat-genere.pdf', $document->chemin);
        Storage::disk('local')->assertMissing($document->chemin);
    }

    public function test_telecharger_refuse_un_fichier_absent_du_disque(): void
    {
        Storage::fake('local');
        $document = Document::factory()->create(['chemin' => 'introuvable.pdf']);

        $this->expectException(DocumentNonAutorise::class);

        app(DocumentService::class)->telecharger($document);
    }
}
