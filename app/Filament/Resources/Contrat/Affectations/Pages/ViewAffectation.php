<?php

namespace App\Filament\Resources\Contrat\Affectations\Pages;

use App\Filament\Resources\Contrat\Affectations\AffectationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAffectation extends ViewRecord
{
    protected static string $resource = AffectationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
