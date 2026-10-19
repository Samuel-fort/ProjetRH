<?php

namespace App\Policies\Embauche;

use App\Models\Embauche\Employe;
use App\Models\User;
use App\Policies\Recrutement\VerifieRoles;

/** Les fiches salariés contiennent des données personnelles : accès restreint. */
class EmployePolicy
{
    use VerifieRoles;

    public function viewAny(User $user): bool
    {
        return $this->aRole($user, 'rh', 'direction', 'finance', 'admin');
    }

    public function view(User $user, Employe $employe): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->aRole($user, 'rh', 'admin');
    }

    public function update(User $user, Employe $employe): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Employe $employe): bool
    {
        return $this->aRole($user, 'admin');
    }
}
