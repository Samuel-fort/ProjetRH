<?php

namespace Tests\Feature\Contrat\Concerns;

use App\Enums\TypeContrat;
use App\Models\Besoin\Departement;
use App\Models\Besoin\Poste;
use App\Models\Embauche\Employe;

trait CreeDonneesContrat
{
    protected function creerEmploye(): Employe
    {
        return Employe::factory()->create();
    }

    /**
     * @return array{departement: Departement, poste: Poste}
     */
    protected function creerPoste(): array
    {
        $departement = Departement::query()->create([
            'nom' => 'Département '.fake()->unique()->numerify('#####'),
            'code' => fake()->unique()->numerify('DEP-#####'),
        ]);

        $poste = Poste::query()->create([
            'intitule' => fake()->unique()->jobTitle(),
            'description' => fake()->sentence(),
            'departement_id' => $departement->getKey(),
        ]);

        return ['departement' => $departement, 'poste' => $poste];
    }

    /**
     * @return array<string, mixed>
     */
    protected function donneesContrat(array $surcharges = []): array
    {
        return array_merge([
            'type' => TypeContrat::Cdd,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
            'salaire' => 3500,
            'horaires' => '08h00 - 17h00',
            'lieu_travail' => 'Antananarivo',
        ], $surcharges);
    }
}
