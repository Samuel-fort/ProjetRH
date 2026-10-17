<?php

namespace App\Listeners;

use App\Events\PropositionAcceptee;
use App\Services\Embauche\GenererContratPdf;
use Throwable;

/**
 * Écouteur : dès qu'une proposition est acceptée, le PDF du contrat est généré automatiquement.
 * Laravel détecte tout seul cet écouteur (dossier app/Listeners + type de l'argument de handle()),
 * aucun fichier partagé n'est à modifier.
 */
class GenererContratPdfApresAcceptation
{
    public function __construct(private GenererContratPdf $generateur)
    {
    }

    public function handle(PropositionAcceptee $evenement): void
    {
        try {
            $this->generateur->depuisProposition($evenement->proposition);
        } catch (Throwable $e) {
            // L'acceptation est déjà enregistrée : un souci de PDF ne doit pas l'annuler.
            // On journalise l'erreur ; le PDF pourra être régénéré avec le bouton de l'écran.
            report($e);
        }
    }
}
