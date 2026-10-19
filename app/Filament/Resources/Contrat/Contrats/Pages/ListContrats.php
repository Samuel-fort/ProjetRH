<?php

namespace App\Filament\Resources\Contrat\Contrats\Pages;

use App\Filament\Resources\Contrat\Contrats\ContratResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContrats extends ListRecords
{
    protected static string $resource = ContratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
