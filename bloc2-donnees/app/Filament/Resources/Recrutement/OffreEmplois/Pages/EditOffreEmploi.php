<?php

namespace App\Filament\Resources\Recrutement\OffreEmplois\Pages;

use App\Enums\StatutOffre;
use App\Filament\Resources\Recrutement\OffreEmplois\OffreEmploiResource;
use App\Services\Recrutement\OffreService;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOffreEmploi extends EditRecord
{
    protected static string $resource = OffreEmploiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publier')
                ->label('Publier')
                ->icon('heroicon-o-megaphone')
                ->color('success')
                ->requiresConfirmation()
                // Le bouton n'apparaît que pour un brouillon ET si le rôle a le droit (policy).
                ->visible(fn () => $this->record->statut === StatutOffre::Brouillon && auth()->user()->can('update', $this->record))
                ->action(fn () => $this->changer(fn (OffreService $s) => $s->publier($this->record), 'Offre publiée')),

            Action::make('cloturer')
                ->label('Clôturer')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->statut === StatutOffre::Publiee && auth()->user()->can('update', $this->record))
                ->action(fn () => $this->changer(fn (OffreService $s) => $s->cloturer($this->record), 'Offre clôturée')),
        ];
    }

    /** Exécute une règle du service, affiche le résultat, et rafraîchit le formulaire. */
    private function changer(callable $operation, string $messageSucces): void
    {
        try {
            $operation(app(OffreService::class));
            Notification::make()->title($messageSucces)->success()->send();
            $this->refreshFormData(['statut']);
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
