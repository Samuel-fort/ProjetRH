<?php

namespace Database\Factories\Contrat;

use App\Enums\StatutTache;
use App\Enums\TypeTacheOnboarding;
use App\Models\Contrat\Onboarding;
use App\Models\Contrat\OnboardingTache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingTache>
 */
class OnboardingTacheFactory extends Factory
{
    protected $model = OnboardingTache::class;

    public function definition(): array
    {
        return [
            'onboarding_id' => Onboarding::factory(),
            'code' => fn (): string => fake()->randomElement(TypeTacheOnboarding::cases())->value,
            'libelle' => function (array $attributes): string {
                return TypeTacheOnboarding::from($attributes['code'])->label();
            },
            'statut' => StatutTache::AFaire,
            'responsable_id' => null,
            'date_echeance' => null,
            'date_realisation' => null,
        ];
    }
}
