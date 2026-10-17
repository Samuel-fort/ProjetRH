<?php

namespace App\Models\Besoin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemandeRecrutement extends Model
{
    protected $table = 'besoin_demande_recrutements';

    protected $fillable = [
        'poste_id',
        'departement_id',
        'demandeur_id',
        'nombre',
        'motif',
        'budget',
        'date_souhaitee',
        'statut',
    ];

    protected $casts = [
        'date_souhaitee' => 'date',
    ];

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(Validation::class, 'demande_recrutement_id');
    }
}