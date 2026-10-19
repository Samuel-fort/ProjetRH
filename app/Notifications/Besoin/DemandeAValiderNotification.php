<?php

namespace App\Notifications\Besoin;

use App\Enums\EtapeValidation;
use App\Models\Besoin\DemandeRecrutement;
use Illuminate\Notifications\Notification;

/**
 * Prevenir le(s) validateur(s) qu'une demande attend leur decision.
 * Canal "database" : la notification est stockee dans la table notifications.
 */
class DemandeAValiderNotification extends Notification
{
    public function __construct(
        public DemandeRecrutement $demande,
        public EtapeValidation $etape,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'demande_id' => $this->demande->id,
            'etape' => $this->etape->value,
            'message' => "Demande #{$this->demande->id} ({$this->demande->poste->intitule}) en attente de votre validation (étape : {$this->etape->value}).",
        ];
    }
}
