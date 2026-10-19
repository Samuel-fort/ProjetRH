<?php

namespace Database\Factories\Recrutement;

use App\Enums\TypeEtape;
use App\Models\Recrutement\Candidature;
use App\Models\Recrutement\Entretien;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntretienFactory extends Factory
{
    protected $model = Entretien::class;

    public function definition(): array
    {
        return [
            'candidature_id' => Candidature::factory(),
            'type' => fake()->randomElement(TypeEtape::cases()),
            'date_heure' => fake()->dateTimeBetween('now', '+14 days'),
            'lieu' => fake()->randomElement(['Salle de réunion A', 'Bureau RH', 'Visioconférence']),
            'intervenant_id' => User::factory(),
        ];
    }
}
