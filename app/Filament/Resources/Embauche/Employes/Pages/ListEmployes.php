<?php

namespace App\Filament\Resources\Embauche\Employes\Pages;

use App\Filament\Resources\Embauche\Employes\EmployeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployes extends ListRecords
{
    protected static string $resource = EmployeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Importer un employé')];
    }
}
