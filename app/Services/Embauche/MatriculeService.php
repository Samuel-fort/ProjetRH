<?php

namespace App\Services\Embauche;

use App\Models\Embauche\Employe;
use Illuminate\Support\Facades\DB;

/**
 * Génère les matricules : EMP-AAAA-NNNN (ex. EMP-2026-0001).
 * La numérotation repart à 0001 chaque année.
 */
class MatriculeService
{
    public function attribuer(Employe $employe): Employe
    {
        // Déjà un matricule : on n'y touche jamais (un matricule ne change pas).
        if ($employe->matricule) {
            return $employe;
        }

        return DB::transaction(function () use ($employe) {
            $prefixe = 'EMP-'.now()->year.'-';

            // lockForUpdate : sur PostgreSQL, bloque les autres attributions simultanées
            // le temps de la transaction, pour que deux employés ne reçoivent pas le même numéro.
            // La contrainte `unique` de la table reste le filet de sécurité final.
            $dernier = Employe::where('matricule', 'like', $prefixe.'%')
                ->lockForUpdate()
                ->pluck('matricule')
                ->map(fn (string $m) => (int) substr($m, strlen($prefixe)))
                ->max() ?? 0;

            $employe->update(['matricule' => $prefixe.str_pad((string) ($dernier + 1), 4, '0', STR_PAD_LEFT)]);

            return $employe;
        });
    }
}
