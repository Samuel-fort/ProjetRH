<?php

namespace App\Policies;

use App\Enums\RoleUtilisateur;
use App\Models\Contrat\OnboardingTache;
use App\Models\User;
use App\Policies\Concerns\VerifieRolesContrat;

class OnboardingTachePolicy
{
    use VerifieRolesContrat;

    public function viewAny(User $user): bool
    {
        return $this->aRole(
            $user,
            RoleUtilisateur::Rh->value,
            RoleUtilisateur::Admin->value,
            RoleUtilisateur::Direction->value,
            RoleUtilisateur::Manager->value,
        );
    }

    public function view(User $user, OnboardingTache $tache): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, OnboardingTache $tache): bool
    {
        return $this->aRole($user, RoleUtilisateur::Rh->value, RoleUtilisateur::Admin->value)
            || (
                $tache->responsable_id !== null
                && (string) $tache->responsable_id === (string) $user->getKey()
            );
    }

    public function delete(User $user, OnboardingTache $tache): bool
    {
        return false;
    }
}
