<?php

namespace Database\Factories\Contrat;

use App\Enums\TypeDocument;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'documentable_type' => (new Contrat)->getMorphClass(),
            'documentable_id' => fn (): int => Contrat::factory()->create()->id,
            'type' => TypeDocument::Autre,
            'nom' => fake()->word().'.pdf',
            'chemin' => 'documents/'.fake()->uuid().'.pdf',
            'disque' => 'local',
            'televerse_par_id' => null,
        ];
    }
}
