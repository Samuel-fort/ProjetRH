<?php

namespace App\Filament\Resources\Recrutement\OffreEmplois\Pages;

use App\Filament\Resources\Recrutement\OffreEmplois\OffreEmploiResource;
use App\Models\Besoin\DemandeRecrutement;
use App\Services\Recrutement\OffreService;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOffreEmploi extends CreateRecord
{
    protected static string $resource = OffreEmploiResource::class;

    /**
     * Par défaut Filament crée l'enregistrement directement.
     * Ici on passe par OffreService pour que la règle métier soit appliquée
     * (offre possible uniquement depuis une demande validée).
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(OffreService::class)->creerDepuisDemande(
                DemandeRecrutement::findOrFail($data['demande_recrutement_id']),
                $data['titre'],
                $data['description'] ?? null,
            );
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $this->halt(); // arrête la création sans planter la page
        }
    }
}
