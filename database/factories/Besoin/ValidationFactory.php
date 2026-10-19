<?php

namespace Database\Factories\Besoin;

use App\Enums\EtapeValidation;
use App\Enums\StatutValidation;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Besoin\Validation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Validation>
 */
class ValidationFactory extends Factory
{
    protected $model = Validation::class;

    public function definition(): array
    {
        return [
            'demande_recrutement_id' => DemandeRecrutement::factory(),
            'validateur_id' => null,
            'etape' => EtapeValidation::Manager,
            'statut' => StatutValidation::EnAttente,
            'commentaire' => null,
            'date_decision' => null,
        ];
    }

    public function etape(EtapeValidation $etape): static
    {
        return $this->state(fn (array $attributes) => ['etape' => $etape]);
    }

    public function validee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => StatutValidation::Validee,
            'date_decision' => now(),
        ]);
    }

    public function refusee(string $commentaire = 'Budget non disponible.'): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => StatutValidation::Refusee,
            'commentaire' => $commentaire,
            'date_decision' => now(),
        ]);
    }
}
