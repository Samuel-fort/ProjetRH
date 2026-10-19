<?php

namespace App\Filament\Resources\Besoin\DemandeRecrutements\Pages;

use App\Enums\StatutDemande;
use App\Filament\Resources\Besoin\DemandeRecrutements\DemandeRecrutementResource;
use App\Models\Besoin\Poste;
use Filament\Resources\Pages\CreateRecord;

class CreateDemandeRecrutement extends CreateRecord
{
    protected static string $resource = DemandeRecrutementResource::class;

    /** Champs que l'utilisateur ne remplit pas : demandeur, departement du poste, statut de depart. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['demandeur_id'] = auth()->id();
        $data['departement_id'] = Poste::findOrFail($data['poste_id'])->departement_id;
        $data['statut'] = StatutDemande::Brouillon;

        return $data;
    }
}
