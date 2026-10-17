<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Statut d'un salarié.
 * Un employé naît `en_integration` (à l'acceptation) ; le Bloc 4 pourra le passer
 * à `actif` quand son onboarding est terminé, puis à `sorti` lors d'une rupture.
 */
enum StatutEmploye: string implements HasLabel, HasColor
{
    case EnIntegration = 'en_integration';
    case Actif = 'actif';
    case Sorti = 'sorti';

    public function getLabel(): string
    {
        return match ($this) {
            self::EnIntegration => "En cours d'intégration",
            self::Actif => 'Actif',
            self::Sorti => 'Sorti',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EnIntegration => 'warning',
            self::Actif => 'success',
            self::Sorti => 'gray',
        };
    }
}
