<?php

namespace App\Services\Contrat;

use App\Models\Contrat\CompteurMatricule;
use App\Models\Embauche\Employe;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class MatriculeService
{
    public function attribuer(Employe $employe, ?CarbonInterface $date = null): string
    {
        return DB::transaction(function () use ($employe, $date): string {
            $employeVerrouille = Employe::query()
                ->whereKey($employe->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($employeVerrouille->matricule !== null) {
                $employe->setAttribute('matricule', $employeVerrouille->matricule);

                return $employeVerrouille->matricule;
            }

            $annee = (int) ($date ?? CarbonImmutable::now())->format('Y');

            try {
                DB::transaction(
                    fn (): CompteurMatricule => CompteurMatricule::query()->firstOrCreate(
                        ['annee' => $annee],
                        ['dernier_numero' => 0]
                    )
                );
            } catch (QueryException $exception) {
                if (! CompteurMatricule::query()->where('annee', $annee)->exists()) {
                    throw $exception;
                }
            }

            $compteur = CompteurMatricule::query()
                ->where('annee', $annee)
                ->lockForUpdate()
                ->firstOrFail();
            $compteur->increment('dernier_numero');

            $matricule = sprintf('EMP-%d-%04d', $annee, $compteur->dernier_numero);
            $employeVerrouille->matricule = $matricule;
            $employeVerrouille->save();
            $employe->setAttribute('matricule', $matricule);

            return $matricule;
        });
    }
}
