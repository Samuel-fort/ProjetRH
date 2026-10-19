<?php

namespace App\Models\Besoin;

use App\Enums\StatutDemande;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne = un changement de statut d'une demande (ex. brouillon -> en_validation).
 * Remplie automatiquement par DemandeRecrutementObserver.
 */
class DemandeHistorique extends Model
{
    protected $table = 'besoin_demande_historiques';

    protected $fillable = [
        'demande_recrutement_id',
        'ancien_statut',
        'nouveau_statut',
        'user_id',
    ];

    protected $casts = [
        'ancien_statut' => StatutDemande::class,
        'nouveau_statut' => StatutDemande::class,
    ];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeRecrutement::class, 'demande_recrutement_id');
    }

    /** L'utilisateur connecte au moment du changement (null si fait en ligne de commande). */
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
