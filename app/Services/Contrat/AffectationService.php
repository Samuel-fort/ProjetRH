<?php

namespace App\Services\Contrat;

use App\Models\Contrat\Affectation;
use App\Models\Embauche\Employe;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AffectationService
{
    public function affecter(
        Employe $employe,
        int $posteId,
        int $departementId,
        ?CarbonInterface $dateDebut = null
    ): Affectation {
        $debut = CarbonImmutable::parse($dateDebut ?? CarbonImmutable::today())->startOfDay();

        return DB::transaction(function () use ($employe, $posteId, $departementId, $debut): Affectation {
            Employe::query()
                ->whereKey($employe->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $affectationCourante = Affectation::query()
                ->where('employe_id', $employe->getKey())
                ->whereNull('date_fin')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($affectationCourante !== null) {
                if ($affectationCourante->date_debut->greaterThanOrEqualTo($debut)) {
                    throw ValidationException::withMessages([
                        'date_debut' => 'La date de début doit être postérieure à celle de l’affectation en cours.',
                    ]);
                }

                $affectationCourante->date_fin = $debut->subDay()->toDateString();
                $affectationCourante->save();
            }

            return Affectation::query()->create([
                'employe_id' => $employe->getKey(),
                'poste_id' => $posteId,
                'departement_id' => $departementId,
                'date_debut' => $debut->toDateString(),
                'date_fin' => null,
            ]);
        });
    }
}
