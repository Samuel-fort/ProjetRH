<?php

namespace Database\Seeders;

use App\Enums\StatutCandidature;
use App\Models\Embauche\Employe;
use App\Models\Embauche\PropositionEmbauche;
use App\Models\Recrutement\Candidature;
use App\Services\Embauche\MatriculeService;
use App\Services\Embauche\PropositionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;

/**
 * Données de démonstration du Bloc 3. À lancer après RecrutementSeeder.
 * Passe par les services : l'employé et son matricule sont créés comme en vrai.
 */
class EmbaucheSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(PropositionService::class);

        // Idempotence : si des propositions existent déjà, on ne refait rien.
        if (PropositionEmbauche::exists()) {
            return;
        }

        // Le candidat retenu du RecrutementSeeder passe tout le parcours : proposition -> envoi -> acceptation.
        $retenue = Candidature::where('statut', StatutCandidature::Retenue)->first();
        if ($retenue) {
            $proposition = $service->creer($retenue, [
                'salaire' => 1800000,
                'horaires' => 'Lundi au vendredi, 8h-17h',
                'lieu_travail' => 'Antananarivo — siège',
                'date_debut' => now()->addDays(20)->toDateString(),
            ]);
            Event::fakeFor(fn () => $service->accepter($service->envoyer($proposition)));
        } else {
            $this->command?->warn('EmbaucheSeeder : aucune candidature retenue (lancez RecrutementSeeder avant).');
        }

        // Un employé "importé" (jamais candidat) pour montrer le cas sans candidat_id.
        $importe = Employe::firstOrCreate(
            ['email' => 'employe.importe@exemple.mg'],
            ['nom' => 'Rabemanana', 'prenom' => 'Voahangy']
        );
        app(MatriculeService::class)->attribuer($importe);
    }
}
