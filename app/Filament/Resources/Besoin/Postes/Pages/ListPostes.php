<?php

namespace App\Filament\Resources\Besoin\Postes\Pages;

use App\Filament\Resources\Besoin\Postes\PosteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostes extends ListRecords
{
    protected static string $resource = PosteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
