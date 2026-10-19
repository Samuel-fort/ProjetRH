<?php

namespace App\Filament\Resources\Besoin\DemandeRecrutements\Pages;

use App\Filament\Resources\Besoin\DemandeRecrutements\DemandeRecrutementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDemandeRecrutements extends ListRecords
{
    protected static string $resource = DemandeRecrutementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
