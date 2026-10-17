<?php

namespace Database\Factories\Embauche;

use App\Enums\StatutCandidature;
use App\Enums\StatutProposition;
use App\Models\Embauche\PropositionEmbauche;
use App\Models\Recrutement\Candidature;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropositionEmbaucheFactory extends Factory
{
    protected $model = PropositionEmbauche::class;

    public function definition(): array
    {
        return [
            // Par défaut : une candidature RETENUE (seule situation où une proposition a un sens).
            'candidature_id' => Candidature::factory()->statut(StatutCandidature::Retenue),
            'salaire' => fake()->numberBetween(800, 3000) * 1000, // en ariary
            'horaires' => 'Lundi au vendredi, 8h-17h',
            'lieu_travail' => 'Antananarivo',
            'date_debut' => now()->addDays(30),
            'statut' => StatutProposition::Brouillon,
        ];
    }

    public function statut(StatutProposition $statut): static
    {
        return $this->state(fn () => ['statut' => $statut]);
    }
}
