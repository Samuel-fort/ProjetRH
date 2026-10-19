<?php

namespace App\Observers\Contrat;

use App\Models\Contrat\Contrat;
use App\Models\Contrat\ContratHistorique;
use BackedEnum;

class ContratObserver
{
    public function created(Contrat $contrat): void
    {
        ContratHistorique::query()->create([
            'contrat_id' => $contrat->getKey(),
            'ancien_statut' => null,
            'nouveau_statut' => $this->valeurStatut($contrat->statut),
            'user_id' => auth()->id(),
        ]);
    }

    public function updated(Contrat $contrat): void
    {
        if (! $contrat->wasChanged('statut')) {
            return;
        }

        ContratHistorique::query()->create([
            'contrat_id' => $contrat->getKey(),
            'ancien_statut' => $this->valeurStatut($contrat->getOriginal('statut')),
            'nouveau_statut' => $this->valeurStatut($contrat->statut),
            'user_id' => auth()->id(),
        ]);
    }

    private function valeurStatut(BackedEnum|string|null $statut): ?string
    {
        return $statut instanceof BackedEnum ? (string) $statut->value : $statut;
    }
}
