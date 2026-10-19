<?php

namespace App\Filament\Resources\Contrat\Documents\Pages;

use App\Enums\TypeDocument;
use App\Filament\Resources\Contrat\Documents\DocumentResource;
use App\Models\Contrat\Contrat;
use App\Models\Embauche\Employe;
use App\Services\Contrat\DocumentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $cible = match ($data['cible_type']) {
            'contrat' => Contrat::query()->findOrFail($data['cible_id']),
            'employe' => Employe::query()->findOrFail($data['cible_id']),
        };

        return app(DocumentService::class)->televerser(
            $cible,
            $data['fichier'],
            TypeDocument::from($data['type']),
            $data['nom'] ?? null,
            auth()->user()
        );
    }
}
