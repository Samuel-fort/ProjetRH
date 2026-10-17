<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Statut d'une proposition d'embauche (Bloc 3).
 *
 *   brouillon -> envoyee -> acceptee
 *                      \--> refusee
 *
 * `brouillon` est un statut ajouté par rapport à la TODOLIST (envoyee/acceptee/refusee) :
 * il permet de préparer la proposition (salaire, horaires...) avant de l'envoyer.
 */
enum StatutProposition: string implements HasLabel, HasColor
{
    case Brouillon = 'brouillon';
    case Envoyee = 'envoyee';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Envoyee => 'Envoyée',
            self::Acceptee => 'Acceptée',
            self::Refusee => 'Refusée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Brouillon => 'gray',
            self::Envoyee => 'warning',
            self::Acceptee => 'success',
            self::Refusee => 'danger',
        };
    }

    /** Une proposition "en cours" bloque la création d'une autre pour la même candidature. */
    public function estEnCours(): bool
    {
        return in_array($this, [self::Brouillon, self::Envoyee, self::Acceptee], true);
    }
}
