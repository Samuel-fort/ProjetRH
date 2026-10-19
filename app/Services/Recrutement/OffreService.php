<?php

namespace App\Services\Recrutement;

use App\Enums\StatutOffre;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Recrutement\OffreEmploi;
use BackedEnum;
use DomainException;

/**
 * Règles métier des OFFRES D'EMPLOI (Bloc 2).
 *
 * Un "service" = une classe qui contient la logique métier, hors des écrans.
 * Avantage : la même règle sert à Filament, à l'API et aux tests, et on la teste facilement.
 * Quand une règle est violée on lance une DomainException (message en français,
 * affichable tel quel à l'utilisateur).
 */
class OffreService
{
    /** Valeur du statut de la demande (Bloc 1) qui autorise l'ouverture du recrutement. */
    private const STATUT_DEMANDE_OUVERT = 'recrutement_ouvert';

    /**
     * Crée une offre (brouillon) à partir d'une demande de recrutement.
     * Règle : UNIQUEMENT si la demande est au statut `recrutement_ouvert`,
     * c'est-à-dire validée par Manager, RH, Finance et Direction.
     */
    public function creerDepuisDemande(DemandeRecrutement $demande, string $titre, ?string $description = null): OffreEmploi
    {
        // Le statut peut être un enum (BackedEnum) ou un texte selon l'implémentation du Bloc 1 :
        // on compare donc sa valeur brute pour ne pas dépendre de ce choix.
        $statut = $demande->statut instanceof BackedEnum ? $demande->statut->value : $demande->statut;

        if ($statut !== self::STATUT_DEMANDE_OUVERT) {
            throw new DomainException(
                "Impossible de créer une offre : la demande n'est pas validée (statut actuel : {$statut})."
            );
        }

        return OffreEmploi::create([
            'demande_recrutement_id' => $demande->getKey(),
            'titre' => $titre,
            'description' => $description,
            'statut' => StatutOffre::Brouillon,
        ]);
    }

    /** Brouillon -> publiée : l'offre devient ouverte aux candidatures. */
    public function publier(OffreEmploi $offre): OffreEmploi
    {
        if ($offre->statut !== StatutOffre::Brouillon) {
            throw new DomainException('Seule une offre en brouillon peut être publiée.');
        }

        $offre->update([
            'statut' => StatutOffre::Publiee,
            'date_publication' => now()->toDateString(),
        ]);

        return $offre;
    }

    /** Publiée -> clôturée : plus aucune candidature n'est acceptée. */
    public function cloturer(OffreEmploi $offre): OffreEmploi
    {
        if ($offre->statut !== StatutOffre::Publiee) {
            throw new DomainException('Seule une offre publiée peut être clôturée.');
        }

        $offre->update([
            'statut' => StatutOffre::Cloturee,
            'date_cloture' => now()->toDateString(),
        ]);

        return $offre;
    }
}
