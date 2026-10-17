<?php

namespace App\Policies\Recrutement;

use App\Models\Recrutement\OffreEmploi;
use App\Models\User;

/**
 * Une "policy" répond à : "cet utilisateur a-t-il le droit de faire cette action ?".
 * Filament l'interroge automatiquement pour afficher/masquer écrans et boutons.
 */
class OffreEmploiPolicy
{
    use VerifieRoles;

    public function viewAny(User $user): bool
    {
        return $this->aRole($user, 'rh', 'manager', 'direction', 'admin');
    }

    public function view(User $user, OffreEmploi $offre): bool
    {
        return $this->viewAny($user);
    }

    // Seule la RH monte, publie et clôture les offres.
    public function create(User $user): bool
    {
        return $this->aRole($user, 'rh', 'admin');
    }

    public function update(User $user, OffreEmploi $offre): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, OffreEmploi $offre): bool
    {
        return $this->aRole($user, 'admin');
    }
}
