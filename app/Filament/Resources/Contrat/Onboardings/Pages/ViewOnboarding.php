<?php

namespace App\Filament\Resources\Contrat\Onboardings\Pages;

use App\Filament\Resources\Contrat\Onboardings\OnboardingResource;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

class ViewOnboarding extends ViewRecord
{
    protected static string $resource = OnboardingResource::class;

    #[On('onboarding-updated')]
    public function refreshOnboarding(): void
    {
        $this->getRecord()->refresh();
    }
}
