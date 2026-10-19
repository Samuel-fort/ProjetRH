<?php

namespace App\Policies;

use App\Enums\RoleUtilisateur;
use App\Models\Contrat\Affectation;
use App\Models\User;
use App\Policies\Concerns\VerifieRolesContrat;

class AffectationPolicy
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

    public function view(User $user, Affectation $affectation): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->aRole($user, RoleUtilisateur::Rh->value, RoleUtilisateur::Admin->value);
    }

    public function update(User $user, Affectation $affectation): bool
    {
        return $this->aRole($user, RoleUtilisateur::Rh->value, RoleUtilisateur::Admin->value);
    }

    public function delete(User $user, Affectation $affectation): bool
    {
        return false;
    }
}
