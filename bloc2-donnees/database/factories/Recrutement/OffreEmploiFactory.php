<?php

namespace Database\Factories\Recrutement;

use App\Enums\StatutOffre;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Recrutement\OffreEmploi;
use Illuminate\Database\Eloquent\Factories\Factory;

class OffreEmploiFactory extends Factory
{
    protected $model = OffreEmploi::class;

    public function definition(): array
    {
        return [
            // Si on ne fournit pas de demande, on en fabrique une via la factory du Bloc 1.
            // (La closure n'est exécutée qu'à la création : la classe de Samuel doit exister à ce moment.)
            'demande_recrutement_id' => fn () => DemandeRecrutement::factory(),
            'titre' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'statut' => StatutOffre::Brouillon,
            'date_publication' => null,
            'date_cloture' => null,
        ];
    }

    /** État "publiée" : OffreEmploi::factory()->publiee()->create() */
    public function publiee(): static
    {
        return $this->state(fn () => [
            'statut' => StatutOffre::Publiee,
            'date_publication' => now()->subDays(3),
            'date_cloture' => now()->addDays(27),
        ]);
    }
}
