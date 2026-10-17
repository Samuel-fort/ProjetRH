<?php

namespace Database\Seeders;

use App\Enums\StatutCandidature;
use App\Enums\TypeEtape;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Recrutement\OffreEmploi;
use App\Models\User;
use App\Services\Recrutement\CandidatureService;
use App\Services\Recrutement\OffreService;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration du Bloc 2 (identiques chez toute l'équipe).
 * On passe par les SERVICES (et non des insertions brutes) : ainsi les règles
 * sont respectées et l'historique des statuts se remplit tout seul.
 *
 * À lancer après BesoinSeeder : `php artisan db:seed --class=RecrutementSeeder`
 * (ou via DatabaseSeeder, géré par Samuel).
 */
class RecrutementSeeder extends Seeder
{
    public function run(): void
    {
        $offres = app(OffreService::class);
        $candidatures = app(CandidatureService::class);

        // Demandes validées (Bloc 1). Si BesoinSeeder n'en a pas créé, on en fabrique deux.
        $demandes = DemandeRecrutement::where('statut', 'recrutement_ouvert')->take(2)->get();
        while ($demandes->count() < 2) {
            $demandes->push(DemandeRecrutement::factory()->create(['statut' => 'recrutement_ouvert']));
        }

        // Évaluateur / intervenant : un utilisateur RH s'il existe, sinon le premier utilisateur.
        $rh = User::where('role', 'rh')->first() ?? User::first() ?? User::factory()->create();

        // Idempotence : on ne recrée pas les offres de démo si on relance le seeder.
        if (OffreEmploi::where('titre', 'Développeur web Laravel')->exists()) {
            return;
        }

        $offreDev = $offres->publier($offres->creerDepuisDemande(
            $demandes[0], 'Développeur web Laravel',
            'Conception et maintenance du SIRH (Laravel, Filament, PostgreSQL).'
        ));
        $offreCompta = $offres->publier($offres->creerDepuisDemande(
            $demandes[1], 'Assistant comptable',
            'Saisie comptable, rapprochements bancaires, reporting mensuel.'
        ));
        $offres->creerDepuisDemande( // une offre encore en brouillon
            $demandes[0], 'Chargé de communication', 'Offre en préparation.'
        );

        $personnes = [
            ['Rakoto', 'Jean', 'jean.rakoto@exemple.mg', '0341234567'],
            ['Rasoa', 'Hanta', 'hanta.rasoa@exemple.mg', '0329876543'],
            ['Andrianina', 'Tiana', 'tiana.andrianina@exemple.mg', '0331112233'],
            ['Randria', 'Mamy', 'mamy.randria@exemple.mg', null],
            ['Razafy', 'Lalaina', 'lalaina.razafy@exemple.mg', '0344445566'],
        ];
        [$jean, $hanta, $tiana, $mamy, $lalaina] = array_map(
            fn ($p) => ['nom' => $p[0], 'prenom' => $p[1], 'email' => $p[2], 'telephone' => $p[3]],
            $personnes
        );

        // Jean : parcours complet -> retenu (c'est lui qui servira au Bloc 3).
        $c = $candidatures->recevoirCandidature($offreDev, $jean);
        $candidatures->preselectionner($c);
        $e = $candidatures->planifierEntretien($c, TypeEtape::Entretien, now()->subDays(2), 'Bureau RH', $rh->id);
        $candidatures->enregistrerEvaluation($c, $rh, 16.5, 'Très bon niveau technique, motivé.', $e);
        $candidatures->retenir($c);

        // Hanta : en test (et postule aussi à l'autre offre -> UN seul Candidat pour 2 candidatures).
        $c = $candidatures->recevoirCandidature($offreDev, $hanta);
        $candidatures->preselectionner($c);
        $candidatures->planifierEntretien($c, TypeEtape::Test, now()->addDays(3), 'Salle informatique', $rh->id);
        $candidatures->recevoirCandidature($offreCompta, $hanta);

        // Tiana : présélectionnée, en attente d'entretien.
        $candidatures->preselectionner($candidatures->recevoirCandidature($offreDev, $tiana));

        // Mamy : refusé après entretien.
        $c = $candidatures->recevoirCandidature($offreCompta, $mamy);
        $candidatures->preselectionner($c);
        $e = $candidatures->planifierEntretien($c, TypeEtape::Entretien, now()->subDay(), 'Visioconférence', $rh->id);
        $candidatures->enregistrerEvaluation($c, $rh, 8, 'Profil insuffisant pour le poste.', $e);
        $candidatures->refuser($c);

        // Lalaina : candidature simplement reçue.
        $candidatures->recevoirCandidature($offreCompta, $lalaina);
    }
}
