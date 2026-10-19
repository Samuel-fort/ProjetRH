<?php

namespace Database\Seeders;

use App\Enums\RoleUtilisateur;
use App\Models\User;
use Illuminate\Database\Seeder;

class UtilisateurSeeder extends Seeder
{
    /**
     * Un utilisateur de test par role : manager@sirh.test, rh@sirh.test,
     * finance@sirh.test, direction@sirh.test, admin@sirh.test
     * (mot de passe : password). Relancable sans doublons.
     */
    public function run(): void
    {
        foreach (RoleUtilisateur::cases() as $role) {
            User::updateOrCreate(
                ['email' => $role->value.'@sirh.test'],
                [
                    'name' => 'Test '.ucfirst($role->value),
                    'password' => 'password', // hache automatiquement par le cast du modele
                    'role' => $role,
                ]
            );
        }
    }
}
