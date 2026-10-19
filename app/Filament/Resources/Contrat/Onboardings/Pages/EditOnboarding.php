<?php

namespace App\Filament\Resources\Contrat\Onboardings\Pages;

use App\Filament\Resources\Contrat\Onboardings\OnboardingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOnboarding extends EditRecord
{
    protected static string $resource = OnboardingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
