<?php

namespace App\Enums;

enum TypeContrat: string
{
    case Cdd = 'cdd';
    case Cdi = 'cdi';
    case Renouvellement = 'renouvellement';
    case Stage = 'stage';

    public function label(): string
    {
        return match ($this) {
            self::Cdd => 'CDD',
            self::Cdi => 'CDI',
            self::Renouvellement => 'Renouvellement',
            self::Stage => 'Stage',
        };
    }

    public function aDureeDeterminee(): bool
    {
        return $this !== self::Cdi;
    }
}
