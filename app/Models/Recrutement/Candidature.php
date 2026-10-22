<?php

namespace App\Models\Recrutement;

use App\Enums\StatutCandidature;
use App\Observers\Recrutement\CandidatureObserver;
use App\Policies\Recrutement\CandidaturePolicy;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Candidature = un candidat qui postule à une offre. C'est l'objet central du Bloc 2 :
 * entretiens, évaluations, historique et (Bloc 3) proposition d'embauche s'y rattachent.
 */
#[ObservedBy(CandidatureObserver::class)] // journalise les changements de statut
#[UsePolicy(CandidaturePolicy::class)] // droits d'accès selon le rôle
class Candidature extends Model
{
    use HasFactory;

    protected $table = 'candidatures';

    protected $fillable = ['offre_emploi_id', 'candidat_id', 'statut', 'date_candidature', 'analyse_preselection'];

    protected function casts(): array
    {
        return [
            'statut' => StatutCandidature::class,
            'date_candidature' => 'date',
            'analyse_preselection' => 'array',
        ];
    }

    public function offreEmploi(): BelongsTo
    {
        return $this->belongsTo(OffreEmploi::class);
    }

    public function candidat(): BelongsTo
    {
        return $this->belongsTo(Candidat::class);
    }

    public function entretiens(): HasMany
    {
        return $this->hasMany(Entretien::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(EvaluationCandidat::class);
    }

    /** Journal des changements de statut (le plus récent d'abord). */
    public function historiques(): HasMany
    {
        return $this->hasMany(CandidatureHistorique::class)->latest('created_at');
    }
}
