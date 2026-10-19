<?php

namespace Database\Factories\Besoin;

use App\Models\Besoin\Departement;
use App\Models\Besoin\Poste;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Poste>
 */
class PosteFactory extends Factory
{
    protected $model = Poste::class;

    public function definition(): array
    {
        return [
            'intitule' => fake()->unique()->jobTitle(),
            'description' => fake()->sentence(12),
            'departement_id' => Departement::factory(),
            'actif' => true,
        ];
    }

    public function inactif(): static
    {
        return $this->state(fn (array $attributes) => ['actif' => false]);
    }
}
