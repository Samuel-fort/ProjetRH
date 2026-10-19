<?php

namespace App\Filament\Resources\Contrat\Affectations\Pages;

use App\Filament\Resources\Contrat\Affectations\AffectationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAffectation extends EditRecord
{
    protected static string $resource = AffectationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
