<?php

namespace Database\Factories\Contrat;

use App\Models\Besoin\Departement;
use App\Models\Besoin\Poste;
use App\Models\Contrat\Affectation;
use App\Models\Embauche\Employe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Affectation>
 */
class AffectationFactory extends Factory
{
    protected $model = Affectation::class;

    public function definition(): array
    {
        return [
            'employe_id' => Employe::factory(),
            'poste_id' => function (): int {
                $departement = Departement::query()->create([
                    'nom' => 'Département '.fake()->unique()->numerify('#######'),
                    'code' => fake()->unique()->numerify('DEP-#####'),
                ]);

                return Poste::query()->create([
                    'intitule' => fake()->unique()->jobTitle(),
                    'description' => fake()->sentence(),
                    'departement_id' => $departement->id,
                ])->id;
            },
            'departement_id' => function (array $attributes): int {
                return Poste::query()->findOrFail($attributes['poste_id'])->departement_id;
            },
            'date_debut' => now()->toDateString(),
            'date_fin' => null,
        ];
    }
}
