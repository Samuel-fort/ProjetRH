<?php

namespace App\Filament\Resources\Contrat\Contrats\Pages;

use App\Filament\Resources\Contrat\Contrats\ContratResource;
use App\Models\Embauche\Employe;
use App\Services\Contrat\ContratService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateContrat extends CreateRecord
{
    protected static string $resource = ContratResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $employe = Employe::query()->findOrFail($data['employe_id']);
        unset($data['employe_id']);

        if (($data['type'] ?? null) === 'cdi') {
            $data['date_fin'] = null;
        }

        return app(ContratService::class)->creer($employe, $data);
    }
}
