<?php

namespace App\Filament\Resources\Embauche\PropositionEmbauches\Pages;

use App\Enums\StatutProposition;
use App\Filament\Actions\SafeDeleteAction;
use App\Filament\Resources\Embauche\PropositionEmbauches\PropositionEmbaucheResource;
use App\Services\Embauche\GenererContratPdf;
use App\Services\Embauche\PropositionService;
use Closure;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Fiche d'une proposition + boutons du workflow : Envoyer -> Accepter / Refuser.
 * Comme pour les candidatures : chaque bouton est affiché selon le statut ET le rôle,
 * et appelle PropositionService (la règle n'est écrite qu'à un seul endroit).
 */
class EditPropositionEmbauche extends EditRecord
{
    protected static string $resource = PropositionEmbaucheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('envoyer')
                ->label('Envoyer au candidat')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->statut === StatutProposition::Brouillon && auth()->user()->can('update', $this->record))
                ->action(fn () => $this->executer(fn (PropositionService $s) => $s->envoyer($this->record), 'Proposition envoyée')),

            Action::make('accepter')
                ->label('Le candidat accepte')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Un employé sera créé avec son matricule, et le contrat PDF sera généré.')
                ->visible(fn () => $this->record->statut === StatutProposition::Envoyee && auth()->user()->can('repondre', $this->record))
                ->action(fn () => $this->executer(fn (PropositionService $s) => $s->accepter($this->record), 'Proposition acceptée : employé créé')),

            Action::make('refuser')
                ->label('Le candidat refuse')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->statut === StatutProposition::Envoyee && auth()->user()->can('repondre', $this->record))
                ->action(fn () => $this->executer(fn (PropositionService $s) => $s->refuser($this->record), 'Proposition refusée')),

            Action::make('telecharger')
                ->label('Télécharger le contrat (PDF)')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => $this->record->contrat_pdf_path && Storage::disk('local')->exists($this->record->contrat_pdf_path)
                    && auth()->user()->can('view', $this->record))
                ->action(fn () => Storage::disk('local')->download($this->record->contrat_pdf_path)),

            Action::make('regenerer')
                ->label('Générer le contrat (PDF)')
                ->icon('heroicon-o-document-arrow-up')
                ->color('gray')
                ->visible(fn () => $this->record->statut === StatutProposition::Acceptee && auth()->user()->can('repondre', $this->record))
                ->action(function () {
                    try {
                        app(GenererContratPdf::class)->depuisProposition($this->record);
                        Notification::make()->title('Contrat généré')->success()->send();
                        $this->record->refresh();
                    } catch (Throwable $e) {
                        Notification::make()->title('Génération impossible')->body($e->getMessage())->danger()->send();
                    }
                }),

            SafeDeleteAction::make()->visible(fn () => $this->record->statut === StatutProposition::Brouillon),
        ];
    }

    /** Une fois envoyée, la proposition est verrouillée : plus de bouton « Enregistrer ». */
    protected function getFormActions(): array
    {
        return PropositionEmbaucheResource::estVerrouillee($this->record) ? [] : parent::getFormActions();
    }

    private function executer(Closure $operation, string $messageSucces): void
    {
        try {
            $operation(app(PropositionService::class));
            Notification::make()->title($messageSucces)->success()->send();
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }

        // Recharge la fiche : nouveau statut, verrouillage des champs, matricule affiché...
        $this->record->refresh();
        $this->fillForm();
    }
}
