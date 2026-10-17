<?php

namespace App\Models\Recrutement;

use App\Enums\StatutCandidature;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne du journal des statuts d'une candidature.
 * Écrite automatiquement par un Observer (étape suivante) ; jamais modifiée.
 */
class CandidatureHistorique extends Model
{
    protected $table = 'candidature_historiques';

    // La table n'a que `created_at` (pas de `updated_at`) : on le dit à Eloquent.
    public const UPDATED_AT = null;

    protected $fillable = ['candidature_id', 'ancien_statut', 'nouveau_statut', 'user_id'];

    protected function casts(): array
    {
        return [
            'ancien_statut' => StatutCandidature::class,
            'nouveau_statut' => StatutCandidature::class,
        ];
    }

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(Candidature::class);
    }

    /** Qui a fait le changement (null = action système ou candidature publique). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
