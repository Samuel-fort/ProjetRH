<?php

namespace App\Policies\Recrutement;

use App\Models\Recrutement\Candidature;
use App\Models\User;

class CandidaturePolicy
{
    use VerifieRoles;

    public function viewAny(User $user): bool
    {
        return $this->aRole($user, 'rh', 'manager', 'direction', 'admin');
    }

    public function view(User $user, Candidature $candidature): bool
    {
        return $this->viewAny($user);
    }

    // Gérer le pipeline (présélection, planification d'entretiens) : RH.
    public function create(User $user): bool
    {
        return $this->aRole($user, 'rh', 'admin');
    }

    public function update(User $user, Candidature $candidature): bool
    {
        return $this->create($user);
    }

    /** Donner une note : ceux qui mènent les entretiens (manager, RH) et la direction. */
    public function evaluer(User $user, Candidature $candidature): bool
    {
        return $this->aRole($user, 'rh', 'manager', 'direction', 'admin');
    }

    /** Décision finale (retenir / refuser) : RH et Direction seulement. */
    public function decider(User $user, Candidature $candidature): bool
    {
        return $this->aRole($user, 'rh', 'direction', 'admin');
    }

    public function delete(User $user, Candidature $candidature): bool
    {
        return $this->aRole($user, 'admin');
    }
}
