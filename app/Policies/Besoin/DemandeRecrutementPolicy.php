<?php

namespace App\Policies\Besoin;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutDemande;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\User;

/**
 * Une policy repond a la question : "cet utilisateur a-t-il le droit de faire
 * cette action sur cette demande ?". Branchee par #[UsePolicy] sur le modele.
 *
 * Choix de conception (faciles a changer) :
 *  - le role Admin peut agir a toutes les etapes (pratique pour tester) ;
 *  - un manager voit ses propres demandes et celles qui attendent sa validation.
 */
class DemandeRecrutementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DemandeRecrutement $demande): bool
    {
        return $user->aLeRole(
            RoleUtilisateur::Rh,
            RoleUtilisateur::Finance,
            RoleUtilisateur::Direction,
            RoleUtilisateur::Admin,
        )
            || $demande->demandeur_id === $user->id
            || $this->valider($user, $demande);
    }

    public function create(User $user): bool
    {
        return $user->aLeRole(RoleUtilisateur::Manager, RoleUtilisateur::Admin);
    }

    /** Modifiable seulement tant que c'est un brouillon, par son auteur (ou un admin). */
    public function update(User $user, DemandeRecrutement $demande): bool
    {
        return $demande->statut === StatutDemande::Brouillon
            && ($demande->demandeur_id === $user->id || $user->aLeRole(RoleUtilisateur::Admin));
    }

    public function delete(User $user, DemandeRecrutement $demande): bool
    {
        return $this->update($user, $demande);
    }

    public function soumettre(User $user, DemandeRecrutement $demande): bool
    {
        return $this->update($user, $demande);
    }

    /** Valider : la demande est en cours et l'etape courante correspond au role de l'utilisateur. */
    public function valider(User $user, DemandeRecrutement $demande): bool
    {
        if ($demande->statut !== StatutDemande::EnValidation) {
            return false;
        }

        $etape = $demande->etapeCourante()?->etape;

        return $etape !== null && $user->aLeRole($etape->role(), RoleUtilisateur::Admin);
    }

    public function refuser(User $user, DemandeRecrutement $demande): bool
    {
        return $this->valider($user, $demande);
    }
}
