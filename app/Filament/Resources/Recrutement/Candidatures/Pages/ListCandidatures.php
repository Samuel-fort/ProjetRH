<?php

namespace App\Filament\Resources\Recrutement\Candidatures\Pages;

use App\Enums\StatutOffre;
use App\Filament\Resources\Recrutement\Candidatures\CandidatureResource;
use App\Models\Recrutement\Candidature;
use App\Models\Recrutement\OffreEmploi;
use App\Services\Recrutement\CandidatureService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCandidatures extends ListRecords
{
    protected static string $resource = CandidatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recevoir')
                ->label('Enregistrer une candidature')
                ->icon('heroicon-o-plus')
                ->visible(fn () => auth()->user()->can('create', Candidature::class))
                // Fenêtre (modal) avec un mini-formulaire, puis appel du service.
                ->schema([
                    Select::make('offre_emploi_id')
                        ->label('Offre')
                        ->options(fn () => OffreEmploi::where('statut', StatutOffre::Publiee)->pluck('titre', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('nom')->label('Nom')->required(),
                    TextInput::make('prenom')->label('Prénom')->required(),
                    TextInput::make('email')->label('Email')->email()->required(),
                    TextInput::make('telephone')->label('Téléphone')->tel(),
                    FileUpload::make('cv_path')->label('CV (PDF)')->disk('public')->directory('cvs')
                        ->acceptedFileTypes(['application/pdf'])->maxSize(5120),
                ])
                ->action(function (array $data) {
                    try {
                        $candidature = app(CandidatureService::class)->recevoirCandidature(
                            OffreEmploi::findOrFail($data['offre_emploi_id']),
                            $data,
                        );
                        Notification::make()->title('Candidature enregistrée')->success()->send();
                        $this->redirect(CandidatureResource::getUrl('view', ['record' => $candidature]));
                    } catch (DomainException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
