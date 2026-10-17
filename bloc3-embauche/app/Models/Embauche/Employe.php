<?php

namespace App\Models\Embauche;

use App\Enums\StatutEmploye;
use App\Models\Recrutement\Candidat;
use App\Policies\Embauche\EmployePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Le salarié. Distinct du Candidat (CANDIDAT ≠ EMPLOYÉ).
 * Le Bloc 4 (Voara) ajoutera ici les relations contrats() et affectations().
 */
#[UsePolicy(EmployePolicy::class)]
class Employe extends Model
{
    use HasFactory;

    protected $table = 'employes';

    protected $fillable = ['candidat_id', 'matricule', 'nom', 'prenom', 'email', 'statut'];

    protected function casts(): array
    {
        return ['statut' => StatutEmploye::class];
    }

    /** Le candidat d'origine (null pour un employé importé). */
    public function candidat(): BelongsTo
    {
        return $this->belongsTo(Candidat::class);
    }

    /** Les propositions d'embauche acceptées qui ont donné ce salarié. */
    public function propositions(): HasMany
    {
        return $this->hasMany(PropositionEmbauche::class);
    }

    public function getNomCompletAttribute(): string
    {
        return "{$this->nom} {$this->prenom}";
    }
}
