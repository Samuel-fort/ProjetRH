<?php

namespace Database\Factories\Recrutement;

use App\Enums\StatutCandidature;
use App\Models\Recrutement\Candidat;
use App\Models\Recrutement\Candidature;
use App\Models\Recrutement\OffreEmploi;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidatureFactory extends Factory
{
    protected $model = Candidature::class;

    public function definition(): array
    {
        return [
            'offre_emploi_id' => OffreEmploi::factory(),
            'candidat_id' => Candidat::factory(),
            'statut' => StatutCandidature::Recue,
            'date_candidature' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /** Candidature à un statut précis : Candidature::factory()->statut(StatutCandidature::Retenue) */
    public function statut(StatutCandidature $statut): static
    {
        return $this->state(fn () => ['statut' => $statut]);
    }
}
