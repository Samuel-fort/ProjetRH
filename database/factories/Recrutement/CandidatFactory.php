<?php

namespace Database\Factories\Recrutement;

use App\Models\Recrutement\Candidat;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Fabrique de faux candidats (seeders et tests). */
class CandidatFactory extends Factory
{
    protected $model = Candidat::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(), // unique : la colonne l'exige
            'telephone' => '03'.fake()->numerify('# ## ### ##'),
            'cv_path' => null,
            'cv_texte' => null,
        ];
    }
}
