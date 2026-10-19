<?php

namespace App\Enums;

enum EtapeValidation: string
{
    case Manager = 'manager';
    case Rh = 'rh';
    case Finance = 'finance';
    case Direction = 'direction';

    /** Position de l'etape dans le workflow : Manager (1) -> RH -> Finance -> Direction (4). */
    public function ordre(): int
    {
        return match ($this) {
            self::Manager => 1,
            self::Rh => 2,
            self::Finance => 3,
            self::Direction => 4,
        };
    }

    /** Role de l'utilisateur qui a le droit de valider cette etape (meme nom que l'etape). */
    public function role(): RoleUtilisateur
    {
        return RoleUtilisateur::from($this->value);
    }

    /** Etape suivante, ou null si c'est la derniere (Direction). */
    public function suivante(): ?self
    {
        foreach (self::cases() as $etape) {
            if ($etape->ordre() === $this->ordre() + 1) {
                return $etape;
            }
        }

        return null;
    }

    /** Texte affiche a l'ecran. */
    public function libelle(): string
    {
        return match ($this) {
            self::Manager => 'Manager',
            self::Rh => 'Ressources humaines',
            self::Finance => 'Finance',
            self::Direction => 'Direction',
        };
    }
}
