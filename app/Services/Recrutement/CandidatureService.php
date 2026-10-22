<?php

namespace App\Services\Recrutement;

use App\Enums\StatutCandidature;
use App\Enums\StatutOffre;
use App\Enums\TypeEtape;
use App\Models\Recrutement\Candidat;
use App\Models\Recrutement\Candidature;
use App\Models\Recrutement\Entretien;
use App\Models\Recrutement\EvaluationCandidat;
use App\Models\Recrutement\OffreEmploi;
use App\Models\User;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Règles métier du pipeline de sélection : réception, présélection,
 * entretiens/tests, évaluations, décision finale.
 *
 * Remarque : on N'écrit PAS ici l'historique des statuts. C'est le rôle de
 * CandidatureObserver, qui s'en charge automatiquement quel que soit
 * l'endroit d'où le statut change (service, écran Filament, seeder...).
 */
class CandidatureService
{
    /**
     * Enregistre une candidature reçue pour une offre.
     *
     * - L'offre doit être publiée.
     * - Le candidat est recherché par email : s'il existe déjà on le RÉUTILISE
     *   (une personne = un seul Candidat, même avec plusieurs candidatures).
     * - Postuler deux fois à la même offre est refusé.
     *
    * @param  array{nom: string, prenom: string, email: string, telephone?: ?string, cv_path?: ?string, cv_texte?: ?string}  $infos
     */
    public function recevoirCandidature(OffreEmploi $offre, array $infos): Candidature
    {
        if ($offre->statut !== StatutOffre::Publiee) {
            throw new DomainException("Cette offre n'accepte pas de candidatures (elle n'est pas publiée).");
        }

        // Emails normalisés (minuscules, sans espaces) pour que "A@x.mg " et "a@x.mg" soient le même candidat.
        $email = mb_strtolower(trim($infos['email']));

        // Transaction : si une étape échoue, rien n'est enregistré (pas de candidat "orphelin").
        return DB::transaction(function () use ($offre, $infos, $email) {
            $candidat = Candidat::firstOrCreate(
                ['email' => $email],
                [
                    'nom' => $infos['nom'],
                    'prenom' => $infos['prenom'],
                    'telephone' => $infos['telephone'] ?? null,
                    'cv_path' => $infos['cv_path'] ?? null,
                    'cv_texte' => $infos['cv_texte'] ?? null,
                ]
            );

            // Candidat déjà connu : on complète son dossier avec ce qui manquait (ex. un CV).
            if (! $candidat->wasRecentlyCreated) {
                $candidat->fill(array_filter([
                    'telephone' => $candidat->telephone ?: ($infos['telephone'] ?? null),
                    'cv_path' => $infos['cv_path'] ?? $candidat->cv_path,
                    'cv_texte' => $infos['cv_texte'] ?? $candidat->cv_texte,
                ]))->save();
            }

            if (Candidature::where('offre_emploi_id', $offre->id)->where('candidat_id', $candidat->id)->exists()) {
                throw new DomainException('Ce candidat a déjà postulé à cette offre.');
            }

            return Candidature::create([
                'offre_emploi_id' => $offre->id,
                'candidat_id' => $candidat->id,
                'statut' => StatutCandidature::Recue,
                'date_candidature' => now()->toDateString(),
            ]);
        });
    }

    /** Changement de statut contrôlé par la "carte" StatutCandidature::transitionsPossibles(). */
    public function changerStatut(Candidature $candidature, StatutCandidature $nouveau): Candidature
    {
        if (! $candidature->statut->peutPasserA($nouveau)) {
            throw new DomainException(
                "Passage impossible : « {$candidature->statut->getLabel()} » vers « {$nouveau->getLabel()} »."
            );
        }

        $candidature->update(['statut' => $nouveau]);

        return $candidature;
    }

    public function preselectionner(Candidature $candidature): Candidature
    {
        return $this->changerStatut($candidature, StatutCandidature::Preselectionnee);
    }

    /** Décision finale positive : c'est le signal pour le Bloc 3 (proposition d'embauche). */
    public function retenir(Candidature $candidature): Candidature
    {
        return $this->changerStatut($candidature, StatutCandidature::Retenue);
    }

    public function refuser(Candidature $candidature): Candidature
    {
        return $this->changerStatut($candidature, StatutCandidature::Refusee);
    }

    /**
     * Planifie une étape (dossier, entretien ou test).
     * Un entretien/test fait avancer la candidature vers `en_entretien` / `en_test`
     * (la candidature doit donc être au moins présélectionnée).
     */
    public function planifierEntretien(
        Candidature $candidature,
        TypeEtape $type,
        DateTimeInterface $dateHeure,
        ?string $lieu = null,
        ?int $intervenantId = null,
    ): Entretien {
        if ($candidature->statut->estFinal()) {
            throw new DomainException('Cette candidature a déjà reçu une décision finale.');
        }

        // Statut visé par ce type d'étape (null = la sélection sur dossier ne change pas le statut).
        $cible = match ($type) {
            TypeEtape::Entretien => StatutCandidature::EnEntretien,
            TypeEtape::Test => StatutCandidature::EnTest,
            TypeEtape::Dossier => null,
        };

        if ($cible && $candidature->statut !== $cible && ! $candidature->statut->peutPasserA($cible)) {
            throw new DomainException(
                "Impossible de planifier un(e) {$type->getLabel()} : la candidature est « {$candidature->statut->getLabel()} »."
            );
        }

        return DB::transaction(function () use ($candidature, $type, $dateHeure, $lieu, $intervenantId, $cible) {
            $entretien = $candidature->entretiens()->create([
                'type' => $type,
                'date_heure' => $dateHeure,
                'lieu' => $lieu,
                'intervenant_id' => $intervenantId,
            ]);

            if ($cible && $candidature->statut !== $cible) {
                $this->changerStatut($candidature, $cible);
            }

            return $entretien;
        });
    }

    /** Enregistre la note (sur 20) et le commentaire d'un évaluateur. */
    public function enregistrerEvaluation(
        Candidature $candidature,
        User $evaluateur,
        float $note,
        ?string $commentaire = null,
        ?Entretien $entretien = null,
    ): EvaluationCandidat {
        if ($note < 0 || $note > 20) {
            throw new DomainException('La note doit être comprise entre 0 et 20.');
        }
        if ($entretien && $entretien->candidature_id !== $candidature->id) {
            throw new DomainException("Cet entretien n'appartient pas à cette candidature.");
        }

        return $candidature->evaluations()->create([
            'entretien_id' => $entretien?->id,
            'evaluateur_id' => $evaluateur->id,
            'note' => $note,
            'commentaire' => $commentaire,
        ]);
    }
}
