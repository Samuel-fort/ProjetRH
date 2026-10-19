<?php

namespace App\Models\Contrat;

use App\Enums\StatutContrat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratHistorique extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'contrat_contrat_historiques';

    protected $fillable = [
        'contrat_id',
        'ancien_statut',
        'nouveau_statut',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ancien_statut' => StatutContrat::class,
            'nouveau_statut' => StatutContrat::class,
            'created_at' => 'datetime',
        ];
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class, 'contrat_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
