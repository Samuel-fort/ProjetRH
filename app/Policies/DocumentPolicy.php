<?php

namespace App\Policies;

use App\Enums\RoleUtilisateur;
use App\Models\Contrat\Document;
use App\Models\User;
use App\Policies\Concerns\VerifieRolesContrat;

class DocumentPolicy
{
    use VerifieRolesContrat;

    public function viewAny(User $user): bool
    {
        return $this->aRole($user, RoleUtilisateur::Rh->value, RoleUtilisateur::Admin->value);
    }

    public function view(User $user, Document $document): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->aRole($user, RoleUtilisateur::Rh->value, RoleUtilisateur::Admin->value);
    }

    public function update(User $user, Document $document): bool
    {
        return false;
    }

    public function delete(User $user, Document $document): bool
    {
        return false;
    }
}
