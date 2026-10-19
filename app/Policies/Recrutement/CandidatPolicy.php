<?php

namespace App\Policies\Recrutement;

use App\Models\Recrutement\Candidat;
use App\Models\User;

class CandidatPolicy
{
    use VerifieRoles;

    public function viewAny(User $user): bool
    {
        return $this->aRole($user, 'rh', 'manager', 'direction', 'admin');
    }

    public function view(User $user, Candidat $candidat): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->aRole($user, 'rh', 'admin');
    }

    public function update(User $user, Candidat $candidat): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Candidat $candidat): bool
    {
        return $this->aRole($user, 'admin');
    }
}
