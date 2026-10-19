<?php

namespace App\Observers\Recrutement;

use App\Models\Recrutement\Candidature;
use App\Models\Recrutement\CandidatureHistorique;
use Illuminate\Support\Facades\Auth;

/**
 * Un "observer" écoute les événements d'un modèle (created, updated...).
 * Ici : TRAÇABILITÉ. Chaque création ou changement de statut d'une candidature
 * écrit une ligne dans `candidature_historiques`, sans que personne n'ait à y penser.
 * Il est rattaché au modèle par l'attribut #[ObservedBy] (aucun fichier partagé à modifier).
 */
class CandidatureObserver
{
    /** Candidature créée : première ligne du journal (ancien statut = null). */
    public function created(Candidature $candidature): void
    {
        $this->journaliser($candidature, null);
    }

    /** Candidature modifiée : on ne journalise que si le STATUT a changé. */
    public function updated(Candidature $candidature): void
    {
        if (! $candidature->wasChanged('statut')) {
            return;
        }

        // À ce moment, getOriginal() contient encore l'ancienne valeur.
        $this->journaliser($candidature, $candidature->getOriginal('statut'));
    }

    private function journaliser(Candidature $candidature, mixed $ancien): void
    {
        CandidatureHistorique::create([
            'candidature_id' => $candidature->id,
            'ancien_statut' => $ancien,
            'nouveau_statut' => $candidature->statut,
            // Utilisateur connecté ; null si la candidature vient d'un formulaire public ou d'un seeder.
            'user_id' => Auth::id(),
        ]);
    }
}
