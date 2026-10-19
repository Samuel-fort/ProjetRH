<?php

namespace App\Filament\Resources\Recrutement\OffreEmplois\Pages;

use App\Filament\Resources\Recrutement\OffreEmplois\OffreEmploiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOffreEmplois extends ListRecords
{
    protected static string $resource = OffreEmploiResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Créer une offre')];
    }
}
