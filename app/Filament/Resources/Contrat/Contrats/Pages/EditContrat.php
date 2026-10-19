<?php

namespace App\Filament\Resources\Contrat\Contrats\Pages;

use App\Filament\Resources\Contrat\Contrats\ContratResource;
use App\Models\Contrat\Contrat;
use App\Services\Contrat\ContratService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditContrat extends EditRecord
{
    protected static string $resource = ContratResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record instanceof Contrat && ($data['type'] ?? null) === 'cdi') {
            $data['date_fin'] = null;
        }

        return app(ContratService::class)->modifier($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
