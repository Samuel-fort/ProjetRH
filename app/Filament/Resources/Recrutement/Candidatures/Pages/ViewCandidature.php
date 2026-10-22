<?php

namespace App\Filament\Resources\Recrutement\Candidatures\Pages;

use App\Enums\StatutCandidature;
use App\Enums\TypeEtape;
use App\Filament\Resources\Recrutement\Candidatures\CandidatureResource;
use App\Models\User;
use App\Services\Recrutement\CandidatureService;
use App\Services\Recrutement\PreselectionCvService;
use Closure;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;

/**
 * Fiche d'une candidature + tous les boutons du pipeline.
 * Chaque bouton :
 *  1. n'apparaît que si le passage de statut est possible (carte StatutCandidature) ;
 *  2. n'apparaît que si le rôle de l'utilisateur le permet (CandidaturePolicy) ;
 *  3. appelle CandidatureService (la règle métier n'est écrite qu'à un seul endroit).
 */
class ViewCandidature extends ViewRecord
{
    protected static string $resource = CandidatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analyser_cv')
                ->label('Analyser le CV avec l’IA')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->visible(fn () => auth()->user()->can('update', $this->record))
                ->action(function (PreselectionCvService $service): void {
                    try {
                        $service->analyserAvecIa($this->record);
                        Notification::make()->title('Analyse IA enregistrée')->success()->send();
                    } catch (DomainException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }

                    $this->record->refresh();
                }),

            Action::make('preselectionner')
                ->label('Présélectionner')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->visible(fn () => $this->peut(StatutCandidature::Preselectionnee, 'update'))
                ->action(fn () => $this->executer(
                    fn (CandidatureService $s) => $s->preselectionner($this->record), 'Candidature présélectionnée')),

            Action::make('planifier')
                ->label('Planifier une étape')
                ->icon('heroicon-o-calendar-days')
                ->visible(fn () => ! $this->record->statut->estFinal() && auth()->user()->can('update', $this->record))
                ->schema([
                    Select::make('type')->label('Type')->options(TypeEtape::class)->required(),
                    DateTimePicker::make('date_heure')->label('Date et heure')->required(),
                    TextInput::make('lieu')->label('Lieu'),
                    Select::make('intervenant_id')->label('Intervenant')
                        ->options(fn () => User::orderBy('name')->pluck('name', 'id'))->searchable(),
                ])
                ->action(fn (array $data) => $this->executer(
                    fn (CandidatureService $s) => $s->planifierEntretien(
                        $this->record,
                        // Le Select peut renvoyer l'enum lui-même ou sa valeur texte : on accepte les deux.
                        $data['type'] instanceof TypeEtape ? $data['type'] : TypeEtape::from($data['type']),
                        Carbon::parse($data['date_heure']),
                        $data['lieu'] ?? null,
                        $data['intervenant_id'] ?? null,
                    ), 'Étape planifiée')),

            Action::make('evaluer')
                ->label('Évaluer')
                ->icon('heroicon-o-star')
                ->visible(fn () => auth()->user()->can('evaluer', $this->record))
                ->schema(fn () => [
                    TextInput::make('note')->label('Note /20')->numeric()->minValue(0)->maxValue(20)->step(0.5)->required(),
                    Textarea::make('commentaire')->label('Commentaire'),
                    Select::make('entretien_id')->label('Étape concernée (facultatif)')
                        ->options($this->record->entretiens->mapWithKeys(fn ($e) => [
                            $e->id => $e->type->getLabel().' — '.$e->date_heure->format('d/m/Y H:i'),
                        ])),
                ])
                ->action(fn (array $data) => $this->executer(
                    fn (CandidatureService $s) => $s->enregistrerEvaluation(
                        $this->record,
                        auth()->user(),
                        (float) $data['note'],
                        $data['commentaire'] ?? null,
                        isset($data['entretien_id']) ? $this->record->entretiens->find($data['entretien_id']) : null,
                    ), 'Évaluation enregistrée')),

            Action::make('retenir')
                ->label('Retenir')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->peut(StatutCandidature::Retenue, 'decider'))
                ->action(fn () => $this->executer(
                    fn (CandidatureService $s) => $s->retenir($this->record), 'Candidature retenue')),

            Action::make('refuser')
                ->label('Refuser')
                ->icon('heroicon-o-hand-thumb-down')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->peut(StatutCandidature::Refusee, 'decider'))
                ->action(fn () => $this->executer(
                    fn (CandidatureService $s) => $s->refuser($this->record), 'Candidature refusée')),
        ];
    }

    /** Le passage vers $cible est-il possible ET autorisé par la policy ($droit) ? */
    private function peut(StatutCandidature $cible, string $droit): bool
    {
        return $this->record->statut->peutPasserA($cible) && auth()->user()->can($droit, $this->record);
    }

    /** Exécute une règle du service, notifie, puis recharge la fiche (statut, historique...). */
    private function executer(Closure $operation, string $messageSucces): void
    {
        try {
            $operation(app(CandidatureService::class));
            Notification::make()->title($messageSucces)->success()->send();
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }

        $this->record->refresh()->load(['entretiens', 'evaluations', 'historiques']);
    }
}
