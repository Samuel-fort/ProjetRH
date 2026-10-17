<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Statut d'une candidature : c'est le "fil rouge" du pipeline de recrutement.
 *
 * Parcours normal :
 *   recue -> preselectionnee -> en_entretien -> en_test -> retenue
 * et à tout moment : -> refusee.
 *
 * `retenue` est le signal pour le Bloc 3 (on peut alors faire une proposition d'embauche).
 */
enum StatutCandidature: string implements HasLabel, HasColor
{
    case Recue = 'recue';
    case Preselectionnee = 'preselectionnee';
    case EnEntretien = 'en_entretien';
    case EnTest = 'en_test';
    case Retenue = 'retenue';
    case Refusee = 'refusee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Recue => 'Reçue',
            self::Preselectionnee => 'Présélectionnée',
            self::EnEntretien => 'En entretien',
            self::EnTest => 'En test',
            self::Retenue => 'Retenue',
            self::Refusee => 'Refusée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Recue => 'gray',
            self::Preselectionnee => 'info',
            self::EnEntretien, self::EnTest => 'warning',
            self::Retenue => 'success',
            self::Refusee => 'danger',
        };
    }

    /** Une décision finale a été prise : plus de changement de statut possible. */
    public function estFinal(): bool
    {
        return in_array($this, [self::Retenue, self::Refusee], true);
    }
}
