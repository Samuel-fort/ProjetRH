<?php

namespace App\Policies;

use App\Enums\RoleUtilisateur;
use App\Models\Contrat\Contrat;
use App\Models\User;
use App\Policies\Concerns\VerifieRolesContrat;

class ContratPolicy
{
    use VerifieRolesContrat;

    public function viewAny(User $user): bool
    {
        return $this->aRole(
            $user,
            RoleUtilisateur::Rh->value,
            RoleUtilisateur::Admin->value,
            RoleUtilisateur::Direction->value,
            RoleUtilisateur::Finance->value,
        );
    }

    public function view(User $user, Contrat $contrat): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->aRole($user, RoleUtilisateur::Rh->value, RoleUtilisateur::Admin->value);
    }

    public function update(User $user, Contrat $contrat): bool
    {
        return $contrat->estModifiable()
            && $this->aRole($user, RoleUtilisateur::Rh->value, RoleUtilisateur::Admin->value);
    }

    public function signer(User $user, Contrat $contrat): bool
    {
        return $this->aRole($user, RoleUtilisateur::Rh->value, RoleUtilisateur::Admin->value);
    }

    public function valider(User $user, Contrat $contrat): bool
    {
        return $this->aRole($user, RoleUtilisateur::Rh->value, RoleUtilisateur::Admin->value);
    }

    public function renouveler(User $user, Contrat $contrat): bool
    {
        return $contrat->estRenouvelable()
            && $this->aRole($user, RoleUtilisateur::Rh->value, RoleUtilisateur::Admin->value);
    }

    public function delete(User $user, Contrat $contrat): bool
    {
        return false;
    }

    public function restore(User $user, Contrat $contrat): bool
    {
        return false;
    }

    public function forceDelete(User $user, Contrat $contrat): bool
    {
        return false;
    }
}
