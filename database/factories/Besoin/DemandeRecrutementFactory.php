<?php

namespace Database\Factories\Besoin;

use App\Enums\MotifDemande;
use App\Enums\StatutDemande;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Besoin\Poste;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandeRecrutement>
 */
class DemandeRecrutementFactory extends Factory
{
    protected $model = DemandeRecrutement::class;

    public function definition(): array
    {
        return [
            // poste_id doit rester AVANT departement_id : la closure ci-dessous le lit.
            'poste_id' => Poste::factory(),
            // La demande vise le departement du poste (coherence des donnees).
            'departement_id' => fn (array $attributes) => Poste::findOrFail($attributes['poste_id'])->departement_id,
            'demandeur_id' => User::factory(),
            'nombre' => fake()->numberBetween(1, 3),
            'motif' => fake()->randomElement(MotifDemande::cases()),
            'budget' => fake()->numberBetween(10, 80) * 100000,
            'date_souhaitee' => fake()->dateTimeBetween('+1 month', '+4 months'),
            'statut' => StatutDemande::Brouillon,
        ];
    }

    public function brouillon(): static
    {
        return $this->state(fn (array $attributes) => ['statut' => StatutDemande::Brouillon]);
    }

    public function enValidation(): static
    {
        return $this->state(fn (array $attributes) => ['statut' => StatutDemande::EnValidation]);
    }

    public function refusee(): static
    {
        return $this->state(fn (array $attributes) => ['statut' => StatutDemande::Refusee]);
    }

    public function recrutementOuvert(): static
    {
        return $this->state(fn (array $attributes) => ['statut' => StatutDemande::RecrutementOuvert]);
    }
}
