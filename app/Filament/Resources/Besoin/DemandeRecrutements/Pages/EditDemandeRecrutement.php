<?php

namespace App\Filament\Resources\Besoin\DemandeRecrutements\Pages;

use App\Filament\Resources\Besoin\DemandeRecrutements\DemandeRecrutementResource;
use App\Models\Besoin\Poste;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDemandeRecrutement extends EditRecord
{
    protected static string $resource = DemandeRecrutementResource::class;

    protected function getHeaderActions(): array
    {
        // Visible seulement si la Policy autorise la suppression (brouillon de l'auteur).
        return [
            DeleteAction::make(),
        ];
    }

    /** Le departement suit toujours celui du poste choisi. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['departement_id'] = Poste::findOrFail($data['poste_id'])->departement_id;

        return $data;
    }
}
