<?php

namespace App\Filament\Resources\Embauche\Employes\Pages;

use App\Filament\Resources\Embauche\Employes\EmployeResource;
use App\Services\Embauche\MatriculeService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEmploye extends EditRecord
{
    protected static string $resource = EmployeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Pour un employé importé qui n'a pas encore de matricule.
            Action::make('matricule')
                ->label('Attribuer un matricule')
                ->icon('heroicon-o-identification')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->matricule === null && auth()->user()->can('update', $this->record))
                ->action(function () {
                    app(MatriculeService::class)->attribuer($this->record);
                    Notification::make()->title("Matricule attribué : {$this->record->matricule}")->success()->send();
                    $this->fillForm();
                }),
        ];
    }
}
