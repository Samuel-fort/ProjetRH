<?php

namespace App\Policies\Concerns;

use App\Models\User;
use BackedEnum;

trait VerifieRolesContrat
{
    public function roleDe(User $user): ?string
    {
        $role = $user->getAttribute('role');

        if ($role instanceof BackedEnum) {
            return (string) $role->value;
        }

        return is_string($role) ? $role : null;
    }

    public function roleDisponible(User $user): bool
    {
        return array_key_exists('role', $user->getAttributes());
    }

    public function aRole(User $user, string ...$roles): bool
    {
        $role = $this->roleDe($user);

        return $role !== null && in_array($role, $roles, true);
    }
}
