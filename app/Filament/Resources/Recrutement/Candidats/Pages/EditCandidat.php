<?php

namespace App\Filament\Resources\Recrutement\Candidats\Pages;

use App\Filament\Resources\Recrutement\Candidats\CandidatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCandidat extends EditRecord
{
    protected static string $resource = CandidatResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
