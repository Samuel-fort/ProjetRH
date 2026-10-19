<?php

namespace App\Filament\Resources\Besoin\DemandeRecrutements\Pages;

use App\Filament\Resources\Besoin\DemandeRecrutements\DemandeRecrutementResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDemandeRecrutement extends ViewRecord
{
    protected static string $resource = DemandeRecrutementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
