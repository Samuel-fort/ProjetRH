<?php

namespace App\Filament\Resources\Contrat\Affectations\Pages;

use App\Filament\Resources\Contrat\Affectations\AffectationResource;
use App\Models\Embauche\Employe;
use App\Services\Contrat\AffectationService;
use Carbon\CarbonImmutable;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAffectation extends CreateRecord
{
    protected static string $resource = AffectationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $employe = Employe::query()->findOrFail($data['employe_id']);

        return app(AffectationService::class)->affecter(
            $employe,
            (int) $data['poste_id'],
            (int) $data['departement_id'],
            CarbonImmutable::parse($data['date_debut'])
        );
    }
}
