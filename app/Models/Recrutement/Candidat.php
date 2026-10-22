<?php

namespace App\Models\Recrutement;

use App\Policies\Recrutement\CandidatPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une personne qui postule. Rappel de conception : CANDIDAT ≠ EMPLOYÉ.
 * Une même personne = un seul Candidat, avec autant de Candidatures que nécessaire.
 */
#[UsePolicy(CandidatPolicy::class)]
class Candidat extends Model
{
    use HasFactory;

    protected $table = 'candidats';

    // Champs modifiables en masse (Candidat::create([...]) / formulaires Filament).
    protected $fillable = ['nom', 'prenom', 'email', 'telephone', 'cv_path', 'cv_texte'];

    /** Toutes les candidatures de cette personne (offres différentes). */
    public function candidatures(): HasMany
    {
        return $this->hasMany(Candidature::class);
    }

    /** Nom complet pour l'affichage : "Rakoto Jean". */
    public function getNomCompletAttribute(): string
    {
        return "{$this->nom} {$this->prenom}";
    }
}
