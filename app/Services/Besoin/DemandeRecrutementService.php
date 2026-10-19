<?php

namespace App\Services\Besoin;

use App\Enums\EtapeValidation;
use App\Enums\RoleUtilisateur;
use App\Enums\StatutDemande;
use App\Enums\StatutValidation;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Besoin\Validation;
use App\Models\User;
use App\Notifications\Besoin\DemandeAValiderNotification;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Toute la logique du workflow : Manager -> RH -> Finance -> Direction.
 * Les ecrans (Filament) appellent ce service, ils ne contiennent pas les regles.
 * En cas de regle non respectee, on leve une DomainException avec un message lisible.
 */
class DemandeRecrutementService
{
    /** Brouillon -> en validation : cree les 4 etapes et previent le manager. */
    public function soumettre(DemandeRecrutement $demande): DemandeRecrutement
    {
        if ($demande->statut !== StatutDemande::Brouillon) {
            throw new DomainException('Seule une demande en brouillon peut être soumise.');
        }

        // Transaction : soit tout reussit, soit rien n'est enregistre.
        DB::transaction(function () use ($demande) {
            foreach (EtapeValidation::cases() as $etape) {
                Validation::firstOrCreate(
                    ['demande_recrutement_id' => $demande->id, 'etape' => $etape->value],
                    ['statut' => StatutValidation::EnAttente],
                );
            }

            $demande->update(['statut' => StatutDemande::EnValidation]);
        });

        $this->notifier($demande, EtapeValidation::Manager);

        return $demande->refresh();
    }

    /** Valide l'etape courante. A la derniere etape (Direction), le recrutement s'ouvre. */
    public function valider(DemandeRecrutement $demande, User $validateur, ?string $commentaire = null): DemandeRecrutement
    {
        $validation = $this->validationAttendue($demande, $validateur);
        $suivante = $validation->etape->suivante();

        DB::transaction(function () use ($demande, $validation, $validateur, $commentaire, $suivante) {
            $validation->update([
                'statut' => StatutValidation::Validee,
                'validateur_id' => $validateur->id,
                'commentaire' => $commentaire,
                'date_decision' => now(),
            ]);

            if ($suivante === null) {
                // Signal pour le Bloc 2 : Lionel peut creer une offre d'emploi.
                $demande->update(['statut' => StatutDemande::RecrutementOuvert]);
            }
        });

        if ($suivante !== null) {
            $this->notifier($demande, $suivante);
        }

        return $demande->refresh();
    }

    /** Refuse a l'etape courante : la demande passe a "refusee". Commentaire obligatoire. */
    public function refuser(DemandeRecrutement $demande, User $validateur, string $commentaire): DemandeRecrutement
    {
        if (trim($commentaire) === '') {
            throw new DomainException('Un commentaire est obligatoire pour refuser une demande.');
        }

        $validation = $this->validationAttendue($demande, $validateur);

        DB::transaction(function () use ($demande, $validation, $validateur, $commentaire) {
            $validation->update([
                'statut' => StatutValidation::Refusee,
                'validateur_id' => $validateur->id,
                'commentaire' => $commentaire,
                'date_decision' => now(),
            ]);

            $demande->update(['statut' => StatutDemande::Refusee]);
        });

        return $demande->refresh();
    }

    /** Verifie les regles et renvoie l'etape que cet utilisateur doit traiter. */
    private function validationAttendue(DemandeRecrutement $demande, User $utilisateur): Validation
    {
        if ($demande->statut !== StatutDemande::EnValidation) {
            throw new DomainException("Cette demande n'est pas en cours de validation.");
        }

        $validation = $demande->etapeCourante();

        if ($validation === null) {
            throw new DomainException('Aucune étape en attente pour cette demande.');
        }

        if (! $utilisateur->aLeRole($validation->etape->role(), RoleUtilisateur::Admin)) {
            throw new DomainException("Votre rôle ne permet pas de valider l'étape « {$validation->etape->value} ».");
        }

        return $validation;
    }

    /** Previent tous les utilisateurs qui ont le role de l'etape. */
    private function notifier(DemandeRecrutement $demande, EtapeValidation $etape): void
    {
        $destinataires = User::where('role', $etape->role()->value)->get();

        Notification::send($destinataires, new DemandeAValiderNotification($demande, $etape));
    }
}
