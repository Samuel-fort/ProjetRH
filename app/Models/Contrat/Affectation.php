<?php

namespace App\Models\Contrat;

use App\Models\Besoin\Departement;
use App\Models\Besoin\Poste;
use App\Models\Embauche\Employe;
use Database\Factories\Contrat\AffectationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affectation extends Model
{
    /** @use HasFactory<AffectationFactory> */
    use HasFactory;

    protected $table = 'contrat_affectations';

    protected $fillable = [
        'employe_id',
        'poste_id',
        'departement_id',
        'date_debut',
        'date_fin',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class, 'poste_id');
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->whereNull('date_fin');
    }
}
