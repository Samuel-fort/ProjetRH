<?php

namespace App\Policies\Recrutement;

use App\Models\User;
use BackedEnum;

/**
 * Petit outil partagé par les policies du Bloc 2 : "l'utilisateur a-t-il l'un de ces rôles ?".
 * La colonne `role` (Bloc 0, Samuel) peut être un enum ou un texte : on lit sa valeur brute.
 * Valeurs attendues : manager, rh, finance, direction, admin.
 */
trait VerifieRoles
{
    protected function aRole(User $user, string ...$roles): bool
    {
        $role = $user->role ?? null;
        $role = $role instanceof BackedEnum ? $role->value : $role;

        return $role !== null && in_array($role, $roles, true);
    }
}
