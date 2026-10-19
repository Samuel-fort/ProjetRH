<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Statut d'une offre d'emploi (Bloc 2).
 *
 * Un "enum" PHP = une liste FERMÉE de valeurs possibles.
 * Avantage : impossible d'écrire "publié" à un endroit et "publiee" ailleurs.
 * La valeur (ex. 'publiee') est ce qui est stocké en base ;
 * le nom du case (ex. Publiee) est ce qu'on utilise dans le code.
 *
 * On implémente HasLabel / HasColor (interfaces Filament) : Filament affiche
 * alors automatiquement un libellé lisible et une pastille colorée.
 */
enum StatutOffre: string implements HasLabel, HasColor
{
    case Brouillon = 'brouillon'; // créée à partir de la demande, pas encore visible
    case Publiee = 'publiee';     // ouverte aux candidatures
    case Cloturee = 'cloturee';   // plus de candidatures acceptées

    public function getLabel(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Publiee => 'Publiée',
            self::Cloturee => 'Clôturée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Brouillon => 'gray',
            self::Publiee => 'success',
            self::Cloturee => 'danger',
        };
    }
}
