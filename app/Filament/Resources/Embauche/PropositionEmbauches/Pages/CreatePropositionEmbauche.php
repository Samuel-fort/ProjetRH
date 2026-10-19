<?php

namespace App\Filament\Resources\Embauche\PropositionEmbauches\Pages;

use App\Filament\Resources\Embauche\PropositionEmbauches\PropositionEmbaucheResource;
use App\Models\Recrutement\Candidature;
use App\Services\Embauche\PropositionService;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePropositionEmbauche extends CreateRecord
{
    protected static string $resource = PropositionEmbaucheResource::class;

    /** On passe par PropositionService : candidature retenue exigée, pas de doublon, données valides. */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(PropositionService::class)->creer(Candidature::findOrFail($data['candidature_id']), $data);
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $this->halt();
        }
    }
}
