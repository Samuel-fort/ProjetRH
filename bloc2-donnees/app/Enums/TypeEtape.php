<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Type d'étape de sélection planifiée pour une candidature (table `entretiens`).
 * D'après le cours : "présélectionner, organiser les entretiens et les tests".
 */
enum TypeEtape: string implements HasLabel
{
    case Dossier = 'dossier';     // sélection sur dossier (CV, lettre)
    case Entretien = 'entretien'; // entretien oral
    case Test = 'test';           // test technique / écrit

    public function getLabel(): string
    {
        return match ($this) {
            self::Dossier => 'Sélection sur dossier',
            self::Entretien => 'Entretien',
            self::Test => 'Test',
        };
    }
}
