<?php

namespace App\Models\Recrutement;

use App\Enums\StatutOffre;
use App\Models\Besoin\DemandeRecrutement; // modèle du Bloc 1 (Samuel)
use App\Policies\Recrutement\OffreEmploiPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Offre d'emploi publiée à partir d'une demande de recrutement validée.
 */
#[UsePolicy(OffreEmploiPolicy::class)]
class OffreEmploi extends Model
{
    use HasFactory;

    protected $table = 'offre_emplois';

    protected $fillable = [
        'demande_recrutement_id', 'titre', 'description',
        'statut', 'date_publication', 'date_cloture',
    ];

    // Les "casts" convertissent automatiquement la valeur stockée en base
    // en objet PHP : 'publiee' (texte) <-> StatutOffre::Publiee (enum), et les dates en Carbon.
    protected function casts(): array
    {
        return [
            'statut' => StatutOffre::class,
            'date_publication' => 'date',
            'date_cloture' => 'date',
        ];
    }

    /** La demande (Bloc 1) à l'origine de l'offre. */
    public function demandeRecrutement(): BelongsTo
    {
        return $this->belongsTo(DemandeRecrutement::class);
    }

    public function candidatures(): HasMany
    {
        return $this->hasMany(Candidature::class);
    }
}
