<?php

namespace App\Models\Contrat;

use App\Enums\StatutOnboarding;
use App\Enums\StatutTache;
use App\Models\Embauche\Employe;
use Database\Factories\Contrat\OnboardingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Onboarding extends Model
{
    /** @use HasFactory<OnboardingFactory> */
    use HasFactory;

    protected $table = 'contrat_onboardings';

    protected $fillable = [
        'employe_id',
        'contrat_id',
        'statut',
        'date_fin',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutOnboarding::class,
            'date_fin' => 'date',
        ];
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class, 'contrat_id');
    }

    public function taches(): HasMany
    {
        return $this->hasMany(OnboardingTache::class, 'onboarding_id');
    }

    /**
     * @return array{faites: int, total: int}
     */
    public function progression(): array
    {
        if ($this->relationLoaded('taches')) {
            $taches = $this->getRelation('taches');

            return [
                'faites' => $taches->where('statut', StatutTache::Fait)->count(),
                'total' => $taches->count(),
            ];
        }

        return [
            'faites' => $this->taches()->where('statut', StatutTache::Fait)->count(),
            'total' => $this->taches()->count(),
        ];
    }

    public function estTermine(): bool
    {
        $progression = $this->progression();

        return $progression['total'] > 0
            && $progression['faites'] === $progression['total'];
    }
}
