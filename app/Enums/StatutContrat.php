<?php

namespace App\Enums;

enum StatutContrat: string
{
    case Cree = 'cree';
    case Signe = 'signe';
    case Valide = 'valide';

    public function label(): string
    {
        return match ($this) {
            self::Cree => 'Créé',
            self::Signe => 'Signé',
            self::Valide => 'Validé',
        };
    }

    public function peutPasserA(self $cible): bool
    {
        return match ($this) {
            self::Cree => $cible === self::Signe,
            self::Signe => $cible === self::Valide,
            self::Valide => false,
        };
    }
}
