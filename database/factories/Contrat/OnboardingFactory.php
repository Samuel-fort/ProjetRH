<?php

namespace Database\Factories\Contrat;

use App\Enums\StatutOnboarding;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Onboarding;
use App\Models\Embauche\Employe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Onboarding>
 */
class OnboardingFactory extends Factory
{
    protected $model = Onboarding::class;

    public function definition(): array
    {
        return [
            'employe_id' => Employe::factory(),
            'contrat_id' => function (array $attributes): int {
                return Contrat::factory()
                    ->create(['employe_id' => $attributes['employe_id']])
                    ->id;
            },
            'statut' => StatutOnboarding::EnCours,
            'date_fin' => null,
        ];
    }
}
