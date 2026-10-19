<?php

namespace App\Filament\Resources\Embauche\PropositionEmbauches\Pages;

use App\Filament\Resources\Embauche\PropositionEmbauches\PropositionEmbaucheResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPropositionEmbauches extends ListRecords
{
    protected static string $resource = PropositionEmbaucheResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouvelle proposition')];
    }
}
