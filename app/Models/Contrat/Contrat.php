<?php

namespace App\Models\Contrat;

use App\Enums\StatutContrat;
use App\Enums\TypeContrat;
use App\Models\Embauche\Employe;
use App\Observers\Contrat\ContratObserver;
use Database\Factories\Contrat\ContratFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy(ContratObserver::class)]
class Contrat extends Model
{
    /** @use HasFactory<ContratFactory> */
    use HasFactory;

    protected $table = 'contrat_contrats';

    protected $fillable = [
        'employe_id',
        'type',
        'date_debut',
        'date_fin',
        'salaire',
        'horaires',
        'lieu_travail',
        'statut',
        'date_signature',
        'date_validation',
        'contrat_precedent_id',
        'proposition_embauche_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeContrat::class,
            'statut' => StatutContrat::class,
            'date_debut' => 'date',
            'date_fin' => 'date',
            'date_signature' => 'date',
            'date_validation' => 'date',
            'salaire' => 'decimal:2',
        ];
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }

    public function precedent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'contrat_precedent_id');
    }

    public function suivant(): HasOne
    {
        return $this->hasOne(self::class, 'contrat_precedent_id');
    }

    public function historiques(): HasMany
    {
        return $this->hasMany(ContratHistorique::class, 'contrat_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function onboarding(): HasOne
    {
        return $this->hasOne(Onboarding::class, 'contrat_id');
    }

    public function estModifiable(): bool
    {
        return $this->statut === StatutContrat::Cree;
    }

    public function estRenouvelable(): bool
    {
        return $this->statut === StatutContrat::Valide
            && $this->date_fin !== null
            && $this->suivant()->doesntExist();
    }
}
