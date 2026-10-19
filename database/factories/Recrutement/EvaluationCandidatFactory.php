<?php

namespace Database\Factories\Recrutement;

use App\Models\Recrutement\Candidature;
use App\Models\Recrutement\EvaluationCandidat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationCandidatFactory extends Factory
{
    protected $model = EvaluationCandidat::class;

    public function definition(): array
    {
        return [
            'candidature_id' => Candidature::factory(),
            'entretien_id' => null,
            'evaluateur_id' => User::factory(),
            'note' => fake()->randomFloat(2, 6, 20), // note sur 20
            'commentaire' => fake()->sentence(),
        ];
    }
}
