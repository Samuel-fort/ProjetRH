<?php

namespace App\Services\Contrat;

use App\Enums\TypeDocument;
use App\Exceptions\Contrat\DocumentNonAutorise;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Document;
use App\Models\Embauche\Employe;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentService
{
    private const array EXTENSIONS_AUTORISEES = ['pdf', 'jpg', 'jpeg', 'png'];

    public function televerser(
        Model $documentable,
        UploadedFile $fichier,
        TypeDocument $type,
        ?string $nom = null,
        ?User $par = null
    ): Document {
        $this->verifierCible($documentable);
        $this->verifierFichier($fichier);

        $repertoire = $this->repertoirePour($documentable);
        $chemin = Storage::disk('local')->putFile($repertoire, $fichier);

        if ($chemin === false) {
            throw new DocumentNonAutorise('Le fichier n’a pas pu être enregistré.');
        }

        try {
            return DB::transaction(fn (): Document => $this->creerDocument(
                $documentable,
                $chemin,
                $type,
                $nom ?? $fichier->getClientOriginalName(),
                'local',
                $par
            ));
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($chemin);

            throw $exception;
        }
    }

    public function enregistrerChemin(
        Model $documentable,
        string $chemin,
        TypeDocument $type,
        ?string $nom = null,
        string $disque = 'local'
    ): Document {
        $this->verifierCible($documentable);

        if (trim($chemin) === '') {
            throw new DocumentNonAutorise('Le chemin du document ne peut pas être vide.');
        }

        return $this->creerDocument($documentable, $chemin, $type, $nom ?? basename($chemin), $disque);
    }

    public function telecharger(Document $document): StreamedResponse
    {
        $disque = Storage::disk($document->disque);

        if (! $disque->exists($document->chemin)) {
            throw new DocumentNonAutorise('Le fichier demandé est introuvable.');
        }

        return $disque->download($document->chemin, $document->nom);
    }

    private function verifierCible(Model $documentable): void
    {
        if (! $documentable instanceof Contrat && ! $documentable instanceof Employe) {
            throw new DocumentNonAutorise('Les documents ne peuvent être rattachés qu’à un salarié ou à un contrat.');
        }

        if (! $documentable->exists || $documentable->getKey() === null) {
            throw new DocumentNonAutorise('La cible du document doit déjà être enregistrée.');
        }
    }

    private function verifierFichier(UploadedFile $fichier): void
    {
        if (! $fichier->isValid()) {
            throw new DocumentNonAutorise('Le téléversement du fichier a échoué.');
        }

        $extension = strtolower($fichier->extension() ?? '');

        if (! in_array($extension, self::EXTENSIONS_AUTORISEES, true)) {
            throw new DocumentNonAutorise('Seuls les fichiers PDF, JPG, JPEG et PNG sont autorisés.');
        }

        if (($fichier->getSize() ?? PHP_INT_MAX) > 10 * 1024 * 1024) {
            throw new DocumentNonAutorise('La taille maximale autorisée est de 10 Mo.');
        }
    }

    private function repertoirePour(Model $documentable): string
    {
        $type = $documentable instanceof Employe ? 'employes' : 'contrats';

        return "contrat/documents/{$type}/{$documentable->getKey()}";
    }

    private function creerDocument(
        Model $documentable,
        string $chemin,
        TypeDocument $type,
        string $nom,
        string $disque,
        ?User $par = null
    ): Document {
        return Document::query()->create([
            'documentable_type' => $documentable->getMorphClass(),
            'documentable_id' => $documentable->getKey(),
            'type' => $type,
            'nom' => $nom,
            'chemin' => $chemin,
            'disque' => $disque,
            'televerse_par_id' => $par?->getKey(),
        ]);
    }
}
