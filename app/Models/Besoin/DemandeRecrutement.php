<?php

namespace App\Models\Besoin;

use App\Enums\MotifDemande;
use App\Enums\StatutDemande;
use App\Enums\StatutValidation;
use App\Models\User;
use App\Observers\Besoin\DemandeRecrutementObserver;
use App\Policies\Besoin\DemandeRecrutementPolicy;
use Database\Factories\Besoin\DemandeRecrutementFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Ces deux attributs branchent l'observer (historique) et la policy (droits).
#[ObservedBy([DemandeRecrutementObserver::class])]
#[UsePolicy(DemandeRecrutementPolicy::class)]
class DemandeRecrutement extends Model
{
    /** @use HasFactory<DemandeRecrutementFactory> */
    use HasFactory;

    protected $table = 'besoin_demande_recrutements';

    protected $fillable = [
        'poste_id',
        'departement_id',
        'demandeur_id',
        'nombre',
        'motif',
        'budget',
        'date_souhaitee',
        'statut',
    ];

    protected $casts = [
        'date_souhaitee' => 'date',
        'motif' => MotifDemande::class,
        'statut' => StatutDemande::class,
    ];

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(Validation::class, 'demande_recrutement_id');
    }

    public function historiques(): HasMany
    {
        return $this->hasMany(DemandeHistorique::class, 'demande_recrutement_id');
    }

    /**
     * L'etape a traiter maintenant : la premiere (dans l'ordre du workflow)
     * dont le statut est "en attente". Null si aucune.
     * On interroge la base a chaque appel pour ne jamais lire une valeur perimee.
     */
    public function etapeCourante(): ?Validation
    {
        return $this->validations()
            ->where('statut', StatutValidation::EnAttente->value)
            ->get()
            ->sortBy(fn (Validation $validation) => $validation->etape->ordre())
            ->first();
    }
}
