<?php

namespace App\Enums;

enum StatutDemande: string
{
    case Brouillon = 'brouillon';
    case EnValidation = 'en_validation';
    case Validee = 'validee';
    case Refusee = 'refusee';
    case RecrutementOuvert = 'recrutement_ouvert';

    /** Texte affiche a l'ecran. */
    public function libelle(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::EnValidation => 'En validation',
            self::Validee => 'Validée',
            self::Refusee => 'Refusée',
            self::RecrutementOuvert => 'Recrutement ouvert',
        };
    }

    /** Couleur du badge Filament. */
    public function couleur(): string
    {
        return match ($this) {
            self::Brouillon => 'gray',
            self::EnValidation => 'warning',
            self::Validee => 'info',
            self::Refusee => 'danger',
            self::RecrutementOuvert => 'success',
        };
    }

    /** Liste [valeur => libelle] pour les listes deroulantes et les filtres. */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $cas) {
            $options[$cas->value] = $cas->libelle();
        }

        return $options;
    }
}
