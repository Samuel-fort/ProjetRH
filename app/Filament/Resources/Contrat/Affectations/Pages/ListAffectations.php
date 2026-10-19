<?php

namespace App\Filament\Resources\Contrat\Affectations\Pages;

use App\Filament\Resources\Contrat\Affectations\AffectationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAffectations extends ListRecords
{
    protected static string $resource = AffectationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
