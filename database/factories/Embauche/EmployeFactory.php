<?php

namespace Database\Factories\Embauche;

use App\Enums\StatutEmploye;
use App\Models\Embauche\Employe;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeFactory extends Factory
{
    protected $model = Employe::class;

    public function definition(): array
    {
        return [
            'candidat_id' => null,   // employé "importé" par défaut
            'matricule' => null,
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'statut' => StatutEmploye::EnIntegration,
        ];
    }
}
