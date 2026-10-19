<?php

namespace App\Filament\Resources\Contrat\Contrats\RelationManagers;

use App\Enums\TypeDocument;
use App\Exceptions\Contrat\DocumentNonAutorise;
use App\Models\Contrat\Document;
use App\Services\Contrat\DocumentService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Nom')->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (TypeDocument $state): string => $state->label()),
                TextColumn::make('televersePar.name')->label('Téléversé par')->placeholder('Inconnu'),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                Action::make('televerser')
                    ->label('Téléverser un document')
                    ->schema([
                        Select::make('type')
                            ->label('Type de document')
                            ->options(collect(TypeDocument::cases())
                                ->mapWithKeys(fn (TypeDocument $type): array => [$type->value => $type->label()])
                                ->all())
                            ->required(),
                        TextInput::make('nom')
                            ->label('Nom')
                            ->maxLength(255),
                        FileUpload::make('fichier')
                            ->label('Fichier')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(10240)
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->authorize(fn (): bool => auth()->user()?->can('create', Document::class) ?? false)
                    ->action(function (array $data): void {
                        try {
                            app(DocumentService::class)->televerser(
                                $this->getOwnerRecord(),
                                $data['fichier'],
                                TypeDocument::from($data['type']),
                                $data['nom'] ?? null,
                                auth()->user()
                            );

                            Notification::make()->success()->title('Document téléversé')->send();
                        } catch (DocumentNonAutorise $exception) {
                            $this->notifierErreur($exception);
                        }
                    }),
            ])
            ->recordActions([
                Action::make('telecharger')
                    ->label('Télécharger')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->authorize(fn (Document $record): bool => auth()->user()?->can('view', $record) ?? false)
                    ->action(fn (Document $record) => app(DocumentService::class)->telecharger($record)),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('televersePar'))
            ->defaultSort('created_at', 'desc');
    }

    private function notifierErreur(Throwable $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Téléversement impossible')
            ->body($exception->getMessage())
            ->send();
    }
}
