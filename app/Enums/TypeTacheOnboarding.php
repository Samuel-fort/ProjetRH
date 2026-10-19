<?php

namespace App\Enums;

enum TypeTacheOnboarding: string
{
    case ContratSigne = 'contrat_signe';
    case Badge = 'badge';
    case Compte = 'compte';
    case Materiel = 'materiel';
    case Formation = 'formation';
    case InscriptionPaie = 'inscription_paie';

    public function label(): string
    {
        return match ($this) {
            self::ContratSigne => 'Contrat signé',
            self::Badge => 'Badge',
            self::Compte => 'Compte informatique',
            self::Materiel => 'Matériel',
            self::Formation => 'Formation',
            self::InscriptionPaie => 'Inscription à la paie',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function ordonnees(): array
    {
        return [
            self::ContratSigne,
            self::Badge,
            self::Compte,
            self::Materiel,
            self::Formation,
            self::InscriptionPaie,
        ];
    }
}
