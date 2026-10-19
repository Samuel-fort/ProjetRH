<?php

namespace App\Models\Besoin;

use App\Enums\EtapeValidation;
use App\Enums\StatutValidation;
use App\Models\User;
use Database\Factories\Besoin\ValidationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Validation extends Model
{
    /** @use HasFactory<ValidationFactory> */
    use HasFactory;

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
        'etape' => EtapeValidation::class,
        'statut' => StatutValidation::class,
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