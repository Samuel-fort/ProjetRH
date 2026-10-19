<?php

namespace Database\Seeders;

use App\Enums\EtapeValidation;
use App\Enums\MotifDemande;
use App\Enums\RoleUtilisateur;
use App\Enums\StatutDemande;
use App\Enums\StatutValidation;
use App\Models\Besoin\Departement;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Besoin\Poste;
use App\Models\Besoin\Validation;
use App\Models\User;
use Illuminate\Database\Seeder;

class BesoinSeeder extends Seeder
{
    /**
     * Doit etre lance apres UtilisateurSeeder (besoin d'un manager et d'un
     * validateur par etape). Relancable sans doublons : departements et postes
     * via firstOrCreate, demandes via firstOrCreate sur (poste, motif, statut).
     */
    public function run(): void
    {
        // --- Departements (hierarchie : direction -> service) ---
        $dg = $this->departement('Direction Generale', 'DG');

        $drh = $this->departement('Direction des Ressources Humaines', 'DRH', $dg);
        $recrutement = $this->departement('Service Recrutement', 'REC', $drh);

        $dsi = $this->departement("Direction des Systemes d'Information", 'DSI', $dg);
        $dev = $this->departement('Service Developpement', 'DEV', $dsi);
        $infra = $this->departement('Service Infrastructure', 'INF', $dsi);

        $daf = $this->departement('Direction Administrative et Financiere', 'DAF', $dg);
        $compta = $this->departement('Service Comptabilite', 'CPT', $daf);

        // --- Postes (existent independamment de tout employe) ---
        $devFullStack = $this->poste('Developpeur Full-Stack', $dev);
        $adminSys = $this->poste('Administrateur Systemes', $infra);
        $chargeRecrut = $this->poste('Charge de recrutement', $recrutement);
        $comptable = $this->poste('Comptable', $compta);
        $chefProjet = $this->poste('Chef de projet', $dsi);

        // --- Demandeur : le manager de test ---
        $manager = User::where('role', RoleUtilisateur::Manager->value)
            ->where('email', 'manager@sirh.test')
            ->firstOrFail();

        // 1. Brouillon : pas de lignes de validation (creees a la soumission)
        $this->demande($manager, $chargeRecrut, MotifDemande::Creation, StatutDemande::Brouillon);

        // 2. En validation : manager OK, les autres en attente
        $this->demande($manager, $devFullStack, MotifDemande::Creation, StatutDemande::EnValidation, [
            EtapeValidation::Manager->value => StatutValidation::Validee,
        ]);

        // 3. En validation : manager + RH OK, finance en attente
        $this->demande($manager, $chefProjet, MotifDemande::Remplacement, StatutDemande::EnValidation, [
            EtapeValidation::Manager->value => StatutValidation::Validee,
            EtapeValidation::Rh->value => StatutValidation::Validee,
        ]);

        // 4. Refusee par la finance
        $this->demande($manager, $comptable, MotifDemande::Creation, StatutDemande::Refusee, [
            EtapeValidation::Manager->value => StatutValidation::Validee,
            EtapeValidation::Rh->value => StatutValidation::Validee,
            EtapeValidation::Finance->value => StatutValidation::Refusee,
        ]);

        // 5. Recrutement ouvert : les 4 etapes validees (signal pour le Bloc 2)
        $this->demande($manager, $adminSys, MotifDemande::Remplacement, StatutDemande::RecrutementOuvert, [
            EtapeValidation::Manager->value => StatutValidation::Validee,
            EtapeValidation::Rh->value => StatutValidation::Validee,
            EtapeValidation::Finance->value => StatutValidation::Validee,
            EtapeValidation::Direction->value => StatutValidation::Validee,
        ]);
    }

    private function departement(string $nom, string $code, ?Departement $parent = null): Departement
    {
        return Departement::firstOrCreate(
            ['nom' => $nom],
            ['code' => $code, 'parent_id' => $parent?->id]
        );
    }

    private function poste(string $intitule, Departement $departement): Poste
    {
        return Poste::firstOrCreate(
            ['departement_id' => $departement->id, 'intitule' => $intitule],
            ['description' => "Poste {$intitule} du departement {$departement->nom}.", 'actif' => true]
        );
    }

    /**
     * Cree une demande et ses 4 lignes de validation (sauf pour un brouillon).
     * Les etapes absentes de $decisions restent « en_attente ».
     *
     * @param  array<string, StatutValidation>  $decisions  cle = valeur de EtapeValidation
     */
    private function demande(
        User $demandeur,
        Poste $poste,
        MotifDemande $motif,
        StatutDemande $statut,
        array $decisions = [],
    ): DemandeRecrutement {
        $demande = DemandeRecrutement::firstOrCreate(
            [
                'poste_id' => $poste->id,
                'motif' => $motif->value,
                'statut' => $statut->value,
            ],
            [
                'departement_id' => $poste->departement_id,
                'demandeur_id' => $demandeur->id,
                'nombre' => 1,
                'budget' => 2500000,
                'date_souhaitee' => now()->addMonths(2),
            ]
        );

        // Demande deja presente (seeder relance) : on ne recree pas les validations.
        if (! $demande->wasRecentlyCreated || $statut === StatutDemande::Brouillon) {
            return $demande;
        }

        foreach (EtapeValidation::cases() as $etape) {
            $decision = $decisions[$etape->value] ?? StatutValidation::EnAttente;
            $decide = $decision !== StatutValidation::EnAttente;

            Validation::create([
                'demande_recrutement_id' => $demande->id,
                'etape' => $etape,
                'statut' => $decision,
                'validateur_id' => $decide ? $this->validateur($etape)->id : null,
                'commentaire' => $decision === StatutValidation::Refusee
                    ? 'Budget non disponible ce trimestre.'
                    : null,
                'date_decision' => $decide ? now() : null,
            ]);
        }

        return $demande;
    }

    /** Une etape correspond au role de meme nom (manager -> manager, rh -> rh, ...). */
    private function validateur(EtapeValidation $etape): User
    {
        $role = RoleUtilisateur::from($etape->value);

        return User::where('email', $role->value.'@sirh.test')->firstOrFail();
    }
}
