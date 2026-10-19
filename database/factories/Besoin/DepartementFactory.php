<?php

namespace Database\Factories\Besoin;

use App\Models\Besoin\Departement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Departement>
 */
class DepartementFactory extends Factory
{
    protected $model = Departement::class;

    public function definition(): array
    {
        return [
            'nom' => 'Departement '.fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'parent_id' => null,
        ];
    }

    /** Cree un service rattache a un departement existant. */
    public function sousDepartementDe(Departement $parent): static
    {
        return $this->state(fn (array $attributes) => ['parent_id' => $parent->id]);
    }
}
