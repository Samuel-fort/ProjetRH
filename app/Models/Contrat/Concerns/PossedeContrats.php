<?php

namespace App\Models\Contrat\Concerns;

use App\Enums\StatutContrat;
use App\Models\Contrat\Affectation;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Document;
use App\Models\Contrat\Onboarding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait PossedeContrats
{
    public function contrats(): HasMany
    {
        return $this->hasMany(Contrat::class, 'employe_id')
            ->orderBy('date_debut')
            ->orderBy('id');
    }

    public function contratEnCours(): HasOne
    {
        return $this->hasOne(Contrat::class, 'employe_id')
            ->ofMany(
                ['date_debut' => 'MAX', 'id' => 'MAX'],
                fn (Builder $query) => $query->where('statut', StatutContrat::Valide)
            )
            ->where('statut', StatutContrat::Valide);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class, 'employe_id');
    }

    public function affectationCourante(): HasOne
    {
        return $this->hasOne(Affectation::class, 'employe_id')
            ->whereNull('date_fin');
    }

    public function onboarding(): HasOne
    {
        return $this->hasOne(Onboarding::class, 'employe_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
