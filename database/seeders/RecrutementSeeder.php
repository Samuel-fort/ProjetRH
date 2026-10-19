<?php

namespace Database\Seeders;

use App\Enums\StatutCandidature;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Recrutement\Candidat;
use App\Models\Recrutement\Candidature;
use App\Models\Recrutement\OffreEmploi;
use Illuminate\Database\Seeder;

class RecrutementSeeder extends Seeder
{
    public function run(): void
    {
        if (OffreEmploi::where('titre', 'Developpeur Full-Stack')->exists()) {
            return;
        }

        $demandes = DemandeRecrutement::query()->orderBy('id')->take(3)->get();

        while ($demandes->count() < 3) {
            $demandes->push(DemandeRecrutement::factory()->create());
        }

        $offres = collect([
            ['titre' => 'Developpeur Full-Stack', 'description' => 'Developpement des applications internes.'],
            ['titre' => 'Administrateur Systemes', 'description' => 'Administration des infrastructures.'],
            ['titre' => 'Charge de recrutement', 'description' => 'Gestion du processus de recrutement.'],
        ])->values()->map(fn (array $donnees, int $index): OffreEmploi => OffreEmploi::factory()
            ->publiee()
            ->create([...$donnees, 'demande_recrutement_id' => $demandes[$index]->id]));

        $candidats = collect([
            ['nom' => 'Rakoto', 'prenom' => 'Jean', 'email' => 'jean.rakoto@exemple.mg'],
            ['nom' => 'Rabe', 'prenom' => 'Paul', 'email' => 'paul.rabe@exemple.mg'],
            ['nom' => 'Andria', 'prenom' => 'Miora', 'email' => 'miora.andria@exemple.mg'],
            ['nom' => 'Rasoanaivo', 'prenom' => 'Hery', 'email' => 'hery.rasoanaivo@exemple.mg'],
            ['nom' => 'Randria', 'prenom' => 'Fara', 'email' => 'fara.randria@exemple.mg'],
        ])->map(fn (array $donnees): Candidat => Candidat::firstOrCreate(
            ['email' => $donnees['email']],
            $donnees
        ));

        $candidatures = [
            [$offres[0], $candidats[0], StatutCandidature::Retenue],
            [$offres[0], $candidats[1], StatutCandidature::Recue],
            [$offres[1], $candidats[2], StatutCandidature::Recue],
            [$offres[1], $candidats[3], StatutCandidature::Recue],
            [$offres[2], $candidats[4], StatutCandidature::Recue],
            [$offres[2], $candidats[0], StatutCandidature::Recue],
        ];

        foreach ($candidatures as [$offre, $candidat, $statut]) {
            Candidature::firstOrCreate(
                ['offre_emploi_id' => $offre->id, 'candidat_id' => $candidat->id],
                ['statut' => $statut, 'date_candidature' => now()->toDateString()]
            );
        }
    }
}
