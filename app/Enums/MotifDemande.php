<?php

namespace App\Enums;

enum MotifDemande: string
{
    case Creation = 'creation';
    case Remplacement = 'remplacement';

    /** Texte affiche a l'ecran. */
    public function libelle(): string
    {
        return match ($this) {
            self::Creation => 'Création de poste',
            self::Remplacement => 'Remplacement',
        };
    }

    /** Liste [valeur => libelle] pour les listes deroulantes. */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $cas) {
            $options[$cas->value] = $cas->libelle();
        }

        return $options;
    }
}
