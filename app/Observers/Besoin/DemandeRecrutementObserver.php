<?php

namespace App\Observers\Besoin;

use App\Models\Besoin\DemandeHistorique;
use App\Models\Besoin\DemandeRecrutement;

/**
 * Un observer "ecoute" les evenements d'un modele (created, updated, deleted...).
 * Il est branche sur DemandeRecrutement par l'attribut #[ObservedBy] du modele.
 */
class DemandeRecrutementObserver
{
    /** Appele apres chaque modification : on note les changements de statut. */
    public function updated(DemandeRecrutement $demande): void
    {
        if (! $demande->wasChanged('statut')) {
            return;
        }

        DemandeHistorique::create([
            'demande_recrutement_id' => $demande->id,
            'ancien_statut' => $demande->getOriginal('statut'),
            'nouveau_statut' => $demande->statut,
            'user_id' => auth()->id(),
        ]);
    }
}
