<?php

namespace App\Models\Recrutement;

use App\Enums\TypeEtape;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Étape planifiée d'une candidature : dossier, entretien ou test. */
class Entretien extends Model
{
    use HasFactory;

    protected $table = 'entretiens';

    protected $fillable = ['candidature_id', 'type', 'date_heure', 'lieu', 'intervenant_id'];

    protected function casts(): array
    {
        return [
            'type' => TypeEtape::class,
            'date_heure' => 'datetime',
        ];
    }

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(Candidature::class);
    }

    /** Utilisateur interne qui mène l'entretien. */
    public function intervenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'intervenant_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(EvaluationCandidat::class);
    }
}
