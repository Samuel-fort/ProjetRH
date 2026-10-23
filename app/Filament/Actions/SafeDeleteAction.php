<?php

namespace App\Filament\Actions;

use App\Models\Besoin\DemandeRecrutement;
use App\Models\Embauche\Employe;
use App\Models\Embauche\PropositionEmbauche;
use App\Models\Recrutement\Candidat;
use App\Models\Recrutement\Candidature;
use App\Models\Recrutement\OffreEmploi;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;

/** Suppression Filament avec indication lorsque des liens protègent l'historique. */
final class SafeDeleteAction
{
    public static function make(): DeleteAction
    {
        return DeleteAction::make()
            ->disabled(fn (Model $record): bool => self::raisonBlocage($record) !== null)
            ->tooltip(fn (Model $record): ?string => self::raisonBlocage($record));
    }

    private static function raisonBlocage(Model $record): ?string
    {
        return match (true) {
            $record instanceof DemandeRecrutement => $record->validations()->exists()
                ? 'Cette demande possède des validations : son historique doit être conservé.'
                : ($record->historiques()->exists()
                    ? 'Cette demande possède un historique : elle ne peut pas être supprimée.'
                    : (OffreEmploi::query()->where('demande_recrutement_id', $record->getKey())->exists()
                        ? 'Une offre de recrutement dépend de cette demande.'
                        : null)),
            $record instanceof Candidat => $record->candidatures()->exists()
                ? 'Ce candidat possède des candidatures : son dossier doit être conservé.'
                : (Employe::query()->where('candidat_id', $record->getKey())->exists()
                    ? 'Ce candidat est lié à une fiche employé.'
                    : null),
            $record instanceof OffreEmploi => $record->candidatures()->exists()
                ? 'Cette offre possède des candidatures : son historique doit être conservé.'
                : null,
            $record instanceof Candidature => $record->historiques()->exists()
                ? 'Cette candidature possède un historique de statut qui doit être conservé.'
                : ($record->entretiens()->exists()
                    ? 'Des entretiens sont liés à cette candidature.'
                    : ($record->evaluations()->exists()
                        ? 'Des évaluations sont liées à cette candidature.'
                        : (PropositionEmbauche::query()->where('candidature_id', $record->getKey())->exists()
                            ? 'Une proposition d’embauche dépend de cette candidature.'
                            : null))),
            $record instanceof Employe => $record->contrats()->exists()
                ? 'Des contrats sont liés à cette fiche employé.'
                : ($record->affectations()->exists()
                    ? 'Des affectations sont liées à cette fiche employé.'
                    : ($record->onboarding()->exists()
                        ? 'Un onboarding est lié à cette fiche employé.'
                        : ($record->documents()->exists()
                            ? 'Des documents sont liés à cette fiche employé.'
                            : (PropositionEmbauche::query()->where('employe_id', $record->getKey())->exists()
                                ? 'Une proposition d’embauche est liée à cette fiche employé.'
                                : null)))),
            default => null,
        };
    }
}
