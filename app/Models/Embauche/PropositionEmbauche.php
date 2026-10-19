<?php

namespace App\Models\Embauche;

use App\Enums\StatutProposition;
use App\Models\Recrutement\Candidature;
use App\Policies\Embauche\PropositionEmbauchePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Proposition d'embauche faite à un candidat retenu. */
#[UsePolicy(PropositionEmbauchePolicy::class)]
class PropositionEmbauche extends Model
{
    use HasFactory;

    protected $table = 'embauche_proposition_embauches';

    protected $fillable = [
        'candidature_id', 'salaire', 'horaires', 'lieu_travail', 'date_debut',
        'statut', 'date_envoi', 'date_reponse', 'employe_id', 'contrat_pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutProposition::class,
            'salaire' => 'decimal:2',
            'date_debut' => 'date',
            'date_envoi' => 'date',
            'date_reponse' => 'date',
        ];
    }

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(Candidature::class);
    }

    /** Renseigné à l'acceptation. */
    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class);
    }
}
