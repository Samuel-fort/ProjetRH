<?php

namespace App\Models\Contrat;

use App\Enums\StatutTache;
use App\Enums\TypeTacheOnboarding;
use App\Models\User;
use Database\Factories\Contrat\OnboardingTacheFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTache extends Model
{
    /** @use HasFactory<OnboardingTacheFactory> */
    use HasFactory;

    protected $table = 'contrat_onboarding_taches';

    protected $fillable = [
        'onboarding_id',
        'code',
        'libelle',
        'statut',
        'responsable_id',
        'date_echeance',
        'date_realisation',
    ];

    protected function casts(): array
    {
        return [
            'code' => TypeTacheOnboarding::class,
            'statut' => StatutTache::class,
            'date_echeance' => 'date',
            'date_realisation' => 'datetime',
        ];
    }

    public function onboarding(): BelongsTo
    {
        return $this->belongsTo(Onboarding::class, 'onboarding_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
