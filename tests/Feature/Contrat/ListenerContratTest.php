<?php

namespace Tests\Feature\Contrat;

use App\Listeners\Contrat\CreerContratApresAcceptation;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Besoin\Departement;
use App\Models\Besoin\Poste;
use App\Models\Embauche\Employe;
use App\Models\Embauche\PropositionEmbauche;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class ListenerContratTest extends TestCase
{
    use RefreshDatabase;

    public function test_acceptation_cree_un_contrat_et_une_affectation_de_maniere_idempotente(): void
    {
        $classeEvenement = 'App\\Events\\PropositionAcceptee';
        $classeProposition = 'App\\Models\\Embauche\\PropositionEmbauche';

        if (! class_exists($classeEvenement) || ! class_exists($classeProposition)) {
            $this->markTestSkipped('Les classes événement et proposition du Bloc 3 ne sont pas encore disponibles.');
        }

        $employe = Employe::factory()->create();
        $departement = Departement::query()->create([
            'nom' => 'Département listener',
            'code' => 'DEP-LISTENER',
        ]);
        $poste = Poste::query()->create([
            'intitule' => 'Poste listener',
            'description' => 'Poste de test',
            'departement_id' => $departement->getKey(),
        ]);
        $demande = new DemandeRecrutement([
            'poste_id' => $poste->getKey(),
            'departement_id' => $departement->getKey(),
        ]);

        $offre = new Model;
        $offre->setRelation('demandeRecrutement', $demande);
        $candidature = new Model;
        $candidature->setRelation('offreEmploi', $offre);

        /** @var PropositionEmbauche $proposition */
        $proposition = new $classeProposition;
        $proposition->setRawAttributes([
            'id' => 702,
            'salaire' => 4400,
            'horaires' => '08h00 - 17h00',
            'lieu_travail' => 'Antananarivo',
            'date_debut' => '2026-10-01',
        ], true);
        $proposition->setRelation('candidature', $candidature);

        $event = (new ReflectionClass($classeEvenement))->newInstanceWithoutConstructor();
        $event->proposition = $proposition;
        $event->employe = $employe;

        $listener = app(CreerContratApresAcceptation::class);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertDatabaseCount('contrat_contrats', 1);
        $this->assertDatabaseCount('contrat_affectations', 1);
        $this->assertDatabaseHas('contrat_contrats', [
            'proposition_embauche_id' => 702,
            'employe_id' => $employe->getKey(),
            'statut' => 'cree',
        ]);
    }
}
