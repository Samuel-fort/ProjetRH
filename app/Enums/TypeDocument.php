<?php

namespace App\Enums;

enum TypeDocument: string
{
    case ContratSigne = 'contrat_signe';
    case ContratGenere = 'contrat_genere';
    case PieceIdentite = 'piece_identite';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::ContratSigne => 'Contrat signé',
            self::ContratGenere => 'Contrat généré',
            self::PieceIdentite => "Pièce d'identité",
            self::Autre => 'Autre',
        };
    }
}
