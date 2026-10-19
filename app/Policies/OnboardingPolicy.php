<?php

namespace App\Policies;

use App\Enums\RoleUtilisateur;
use App\Models\Contrat\Onboarding;
use App\Models\User;
use App\Policies\Concerns\VerifieRolesContrat;

class OnboardingPolicy
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

    public function view(User $user, Onboarding $onboarding): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Onboarding $onboarding): bool
    {
        return false;
    }

    public function delete(User $user, Onboarding $onboarding): bool
    {
        return false;
    }
}
