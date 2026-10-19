<?php

namespace App\Services\Contrat;

use App\Models\Embauche\PropositionEmbauche;
use UnexpectedValueException;

/**
 * Point d'adaptation unique vers les Blocs 2 et 3 : ajuster ici si les noms de relations ou de colonnes diffèrent.
 */
class DonneesContratDepuisProposition
{
    public function extraire(PropositionEmbauche $proposition): array
    {
        foreach (['candidature'] as $relation) {
            if (! method_exists($proposition, $relation)) {
                throw new UnexpectedValueException("La relation {$relation} est absente de la proposition d’embauche.");
            }
        }

        $candidature = $proposition->candidature;
        if ($candidature === null || ! method_exists($candidature, 'offreEmploi')) {
            throw new UnexpectedValueException('La candidature ou sa relation avec l’offre d’emploi est absente.');
        }

        $offreEmploi = $candidature->offreEmploi;
        if ($offreEmploi === null || ! method_exists($offreEmploi, 'demandeRecrutement')) {
            throw new UnexpectedValueException('L’offre d’emploi ou sa demande de recrutement est absente.');
        }

        $demande = $offreEmploi->demandeRecrutement;
        if ($demande === null || $demande->poste_id === null || $demande->departement_id === null) {
            throw new UnexpectedValueException('La demande de recrutement ne fournit pas le poste et le département.');
        }

        foreach (['salaire', 'horaires', 'lieu_travail', 'date_debut'] as $attribut) {
            if ($proposition->getAttribute($attribut) === null) {
                throw new UnexpectedValueException("La proposition d’embauche ne fournit pas l’attribut {$attribut}.");
            }
        }

        if ($proposition->getKey() === null) {
            throw new UnexpectedValueException('La proposition d’embauche doit être enregistrée.');
        }

        return [
            'proposition_embauche_id' => $proposition->getKey(),
            'salaire' => $proposition->salaire,
            'horaires' => $proposition->horaires,
            'lieu_travail' => $proposition->lieu_travail,
            'date_debut' => $proposition->date_debut,
            'poste_id' => $demande->poste_id,
            'departement_id' => $demande->departement_id,
        ];
    }
}
