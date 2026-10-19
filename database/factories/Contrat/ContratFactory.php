<?php

namespace Database\Factories\Contrat;

use App\Enums\StatutContrat;
use App\Enums\TypeContrat;
use App\Models\Contrat\Contrat;
use App\Models\Embauche\Employe;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contrat>
 */
class ContratFactory extends Factory
{
    protected $model = Contrat::class;

    public function definition(): array
    {
        $dateDebut = CarbonImmutable::parse(fake()->dateTimeBetween('-1 month', '+1 month'));

        return [
            'employe_id' => Employe::factory(),
            'type' => TypeContrat::Cdd,
            'date_debut' => $dateDebut->toDateString(),
            'date_fin' => $dateDebut->addMonths(12)->toDateString(),
            'salaire' => fake()->randomFloat(2, 1500, 9000),
            'horaires' => '08h00 - 17h00',
            'lieu_travail' => fake()->city(),
            'statut' => StatutContrat::Cree,
            'date_signature' => null,
            'date_validation' => null,
            'contrat_precedent_id' => null,
            'proposition_embauche_id' => null,
        ];
    }

    public function cdi(): static
    {
        return $this->state([
            'type' => TypeContrat::Cdi,
            'date_fin' => null,
        ]);
    }

    public function cdd(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TypeContrat::Cdd,
            'date_fin' => CarbonImmutable::parse($attributes['date_debut'])
                ->addMonths(12)
                ->toDateString(),
        ]);
    }

    // Ces états posent directement les statuts pour les fixtures de test, sans passer par les services.
    public function signe(): static
    {
        return $this->state([
            'statut' => StatutContrat::Signe,
            'date_signature' => now()->toDateString(),
        ]);
    }

    public function valide(): static
    {
        return $this->state([
            'statut' => StatutContrat::Valide,
            'date_signature' => now()->toDateString(),
            'date_validation' => now()->toDateString(),
        ]);
    }
}
