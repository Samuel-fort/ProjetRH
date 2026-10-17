<?php

namespace App\Events;

use App\Models\Embauche\PropositionEmbauche;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ÉVÉNEMENT : "une proposition d'embauche vient d'être acceptée".
 *
 * Un événement est un signal. Celui qui le déclenche (PropositionService, Bloc 3)
 * ne sait pas qui l'écoute : c'est ce qui permet au Bloc 4 (Voara) de réagir
 * (créer le Contrat et l'Affectation) sans que le code du Bloc 3 dépende du sien.
 *
 * À ce moment, l'Employe existe déjà, avec son matricule : `$proposition->employe`.
 */
class PropositionAcceptee
{
    use Dispatchable, SerializesModels;

    public function __construct(public PropositionEmbauche $proposition)
    {
    }
}
