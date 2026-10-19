<?php

namespace App\Filament\Resources\Contrat\Contrats\Pages;

use App\Enums\StatutContrat;
use App\Enums\TypeContrat;
use App\Enums\TypeDocument;
use App\Exceptions\Contrat\DocumentNonAutorise;
use App\Exceptions\Contrat\RenouvellementImpossible;
use App\Exceptions\Contrat\TransitionContratInvalide;
use App\Filament\Resources\Contrat\Contrats\ContratResource;
use App\Models\Contrat\Contrat;
use App\Services\Contrat\ContratService;
use App\Services\Contrat\DocumentService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Throwable;

class ViewContrat extends ViewRecord
{
    protected static string $resource = ContratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Contrat $record): bool => $record->estModifiable()),
            Action::make('signer')
                ->label('Signer')
                ->color('warning')
                ->schema([
                    DatePicker::make('date_signature')
                        ->label('Date de signature')
                        ->default(now()->toDateString())
                        ->required(),
                    FileUpload::make('fichier')
                        ->label('Contrat signé')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->storeFiles(false),
                ])
                ->visible(fn (Contrat $record): bool => $record->statut === StatutContrat::Cree)
                ->authorize(fn (Contrat $record): bool => auth()->user()?->can('signer', $record) ?? false)
                ->action(function (Contrat $record, array $data): void {
                    try {
                        $contrat = app(ContratService::class)->signer(
                            $record,
                            CarbonImmutable::parse($data['date_signature'])
                        );

                        if (($data['fichier'] ?? null) !== null) {
                            app(DocumentService::class)->televerser(
                                $contrat,
                                $data['fichier'],
                                TypeDocument::ContratSigne,
                                null,
                                auth()->user()
                            );
                        }

                        Notification::make()
                            ->success()
                            ->title('Contrat signé')
                            ->body('Matricule attribué : '.$contrat->employe->matricule)
                            ->send();
                    } catch (TransitionContratInvalide|DocumentNonAutorise $exception) {
                        $this->notifierErreur($exception);
                    }
                }),
            Action::make('valider')
                ->label('Valider')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Contrat $record): bool => $record->statut === StatutContrat::Signe)
                ->authorize(fn (Contrat $record): bool => auth()->user()?->can('valider', $record) ?? false)
                ->action(function (Contrat $record): void {
                    try {
                        app(ContratService::class)->valider($record);

                        Notification::make()
                            ->success()
                            ->title('Contrat validé')
                            ->body('L’onboarding et ses tâches ont été créés.')
                            ->send();
                    } catch (TransitionContratInvalide $exception) {
                        $this->notifierErreur($exception);
                    }
                }),
            Action::make('renouveler')
                ->label('Renouveler')
                ->color('primary')
                ->schema([
                    Select::make('type')
                        ->label('Type du nouveau contrat')
                        ->options(collect(TypeContrat::cases())
                            ->mapWithKeys(fn (TypeContrat $type): array => [$type->value => $type->label()])
                            ->all())
                        ->default(TypeContrat::Renouvellement->value)
                        ->live()
                        ->required(),
                    DatePicker::make('date_debut')
                        ->label('Date de début')
                        ->default(fn (Contrat $record): string => $record->date_fin->addDay()->toDateString())
                        ->required(),
                    DatePicker::make('date_fin')
                        ->label('Date de fin')
                        ->visible(fn (Get $get): bool => TypeContrat::tryFrom((string) $get('type'))?->aDureeDeterminee() ?? false)
                        ->required(fn (Get $get): bool => TypeContrat::tryFrom((string) $get('type'))?->aDureeDeterminee() ?? false),
                    TextInput::make('salaire')
                        ->label('Salaire')
                        ->numeric()
                        ->minValue(0)
                        ->default(fn (Contrat $record): string => $record->salaire)
                        ->required(),
                    TextInput::make('horaires')
                        ->label('Horaires')
                        ->default(fn (Contrat $record): string => $record->horaires)
                        ->required(),
                    TextInput::make('lieu_travail')
                        ->label('Lieu de travail')
                        ->default(fn (Contrat $record): string => $record->lieu_travail)
                        ->required(),
                ])
                ->visible(fn (Contrat $record): bool => $record->estRenouvelable())
                ->authorize(fn (Contrat $record): bool => auth()->user()?->can('renouveler', $record) ?? false)
                ->action(function (Contrat $record, array $data) {
                    try {
                        $nouveauContrat = app(ContratService::class)->renouveler($record, $data);

                        Notification::make()
                            ->success()
                            ->title('Renouvellement créé')
                            ->send();

                        $this->redirect(ContratResource::getUrl('view', ['record' => $nouveauContrat]));
                    } catch (RenouvellementImpossible|TransitionContratInvalide $exception) {
                        $this->notifierErreur($exception);
                    }
                }),
        ];
    }

    private function notifierErreur(Throwable $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Action impossible')
            ->body($exception->getMessage())
            ->send();
    }
}
