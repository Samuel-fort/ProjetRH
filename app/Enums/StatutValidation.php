<?php

namespace App\Enums;

enum StatutValidation: string
{
    case EnAttente = 'en_attente';
    case Validee = 'validee';
    case Refusee = 'refusee';

    /** Texte affiche a l'ecran. */
    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Validee => 'Validée',
            self::Refusee => 'Refusée',
        };
    }

    /** Couleur du badge Filament. */
    public function couleur(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Validee => 'success',
            self::Refusee => 'danger',
        };
    }
}
