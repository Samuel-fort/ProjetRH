<?php

namespace App\Models\Contrat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompteurMatricule extends Model
{
    use HasFactory;

    protected $table = 'contrat_compteur_matricules';

    protected $fillable = [
        'annee',
        'dernier_numero',
    ];

    protected function casts(): array
    {
        return [
            'annee' => 'integer',
            'dernier_numero' => 'integer',
        ];
    }
}
