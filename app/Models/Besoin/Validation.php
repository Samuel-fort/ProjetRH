<?php

namespace App\Models\Besoin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Validation extends Model
{
    protected $table = 'besoin_validations';

    protected $fillable = [
        'demande_recrutement_id',
        'validateur_id',
        'etape',
        'statut',
        'commentaire',
        'date_decision',
    ];

    protected $casts = [
        'date_decision' => 'datetime',
    ];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeRecrutement::class, 'demande_recrutement_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }
}