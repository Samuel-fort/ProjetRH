<?php

namespace App\Policies\Embauche;

use App\Models\Embauche\PropositionEmbauche;
use App\Models\User;
use App\Policies\Recrutement\VerifieRoles;

/** Qui peut faire quoi sur les propositions d'embauche (rôles : manager, rh, finance, direction, admin). */
class PropositionEmbauchePolicy
{
    use VerifieRoles;

    public function viewAny(User $user): bool
    {
        return $this->aRole($user, 'rh', 'direction', 'finance', 'admin');
    }

    public function view(User $user, PropositionEmbauche $proposition): bool
    {
        return $this->viewAny($user);
    }

    /** Préparer, modifier et envoyer une proposition : RH et Direction. */
    public function create(User $user): bool
    {
        return $this->aRole($user, 'rh', 'direction', 'admin');
    }

    public function update(User $user, PropositionEmbauche $proposition): bool
    {
        return $this->create($user);
    }

    /** Enregistrer la réponse du candidat (accepté / refusé) : RH seulement. */
    public function repondre(User $user, PropositionEmbauche $proposition): bool
    {
        return $this->aRole($user, 'rh', 'admin');
    }

    public function delete(User $user, PropositionEmbauche $proposition): bool
    {
        return $this->aRole($user, 'admin');
    }
}
