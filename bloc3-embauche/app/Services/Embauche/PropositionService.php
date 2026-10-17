<?php

namespace App\Services\Embauche;

use App\Enums\StatutCandidature;
use App\Enums\StatutEmploye;
use App\Enums\StatutProposition;
use App\Events\PropositionAcceptee;
use App\Models\Embauche\Employe;
use App\Models\Embauche\PropositionEmbauche;
use App\Models\Recrutement\Candidature;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Règles métier du Bloc 3 : de la candidature retenue à l'employé.
 *
 *   créer (brouillon) -> envoyer -> accepter  => Employe + matricule + événement PropositionAcceptee
 *                                 \-> refuser
 */
class PropositionService
{
    public function __construct(private MatriculeService $matricules)
    {
    }

    /**
     * Prépare une proposition (brouillon) pour une candidature.
     * Règles : la candidature doit être RETENUE, et il ne doit pas déjà exister
     * une proposition en cours (brouillon, envoyée ou acceptée) pour elle.
     *
     * @param  array{salaire: int|float|string, horaires: string, lieu_travail: string, date_debut: mixed}  $donnees
     */
    public function creer(Candidature $candidature, array $donnees): PropositionEmbauche
    {
        if ($candidature->statut !== StatutCandidature::Retenue) {
            throw new DomainException(
                "Impossible de faire une proposition : la candidature n'est pas retenue (statut : {$candidature->statut->getLabel()})."
            );
        }

        $enCours = PropositionEmbauche::where('candidature_id', $candidature->id)->get()
            ->contains(fn (PropositionEmbauche $p) => $p->statut->estEnCours());
        if ($enCours) {
            throw new DomainException('Une proposition est déjà en cours pour cette candidature.');
        }

        if ((float) $donnees['salaire'] <= 0) {
            throw new DomainException('Le salaire doit être supérieur à 0.');
        }
        if (now()->startOfDay()->gt(Carbon::parse($donnees['date_debut']))) {
            throw new DomainException('La date de début ne peut pas être dans le passé.');
        }

        return PropositionEmbauche::create([
            'candidature_id' => $candidature->id,
            'salaire' => $donnees['salaire'],
            'horaires' => $donnees['horaires'],
            'lieu_travail' => $donnees['lieu_travail'],
            'date_debut' => $donnees['date_debut'],
            'statut' => StatutProposition::Brouillon,
        ]);
    }

    /** Brouillon -> envoyée au candidat. */
    public function envoyer(PropositionEmbauche $proposition): PropositionEmbauche
    {
        $this->exiger($proposition, StatutProposition::Brouillon, 'Seule une proposition en brouillon peut être envoyée.');

        $proposition->update(['statut' => StatutProposition::Envoyee, 'date_envoi' => now()->toDateString()]);

        return $proposition;
    }

    /**
     * Le candidat accepte.
     *  1. la proposition passe à `acceptee` (avec la date de réponse) ;
     *  2. l'Employe est créé à partir du Candidat, SANS doublon, et reçoit son matricule ;
     *  3. l'événement PropositionAcceptee est déclenché (Bloc 4 : contrat + affectation ; PDF).
     * Tout se fait dans une transaction : si une étape échoue, rien n'est enregistré.
     */
    public function accepter(PropositionEmbauche $proposition): PropositionEmbauche
    {
        $this->exiger($proposition, StatutProposition::Envoyee, 'Seule une proposition envoyée peut être acceptée.');

        DB::transaction(function () use ($proposition) {
            $candidat = $proposition->candidature->candidat;

            // Une personne = un seul employé : si ce candidat a déjà une fiche (ré-embauche), on la réutilise.
            $employe = Employe::firstOrCreate(
                ['candidat_id' => $candidat->id],
                [
                    'nom' => $candidat->nom,
                    'prenom' => $candidat->prenom,
                    'email' => $candidat->email,
                    'statut' => StatutEmploye::EnIntegration,
                ]
            );

            $this->matricules->attribuer($employe); // sans effet s'il en a déjà un

            $proposition->update([
                'statut' => StatutProposition::Acceptee,
                'date_reponse' => now()->toDateString(),
                'employe_id' => $employe->id,
            ]);
        });

        // Après la transaction : les écouteurs (PDF, Bloc 4) voient des données déjà enregistrées.
        PropositionAcceptee::dispatch($proposition->fresh());

        return $proposition->refresh();
    }

    /** Le candidat refuse : aucun employé créé, aucun événement. */
    public function refuser(PropositionEmbauche $proposition): PropositionEmbauche
    {
        $this->exiger($proposition, StatutProposition::Envoyee, 'Seule une proposition envoyée peut être refusée.');

        $proposition->update(['statut' => StatutProposition::Refusee, 'date_reponse' => now()->toDateString()]);

        return $proposition;
    }

    private function exiger(PropositionEmbauche $proposition, StatutProposition $attendu, string $message): void
    {
        if ($proposition->statut !== $attendu) {
            throw new DomainException($message);
        }
    }
}
