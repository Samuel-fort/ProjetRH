<?php

namespace App\Filament\Resources\Contrat\Documents\Tables;

use App\Enums\TypeDocument;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\Document;
use App\Models\Embauche\Employe;
use App\Services\Contrat\DocumentService;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Nom')->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (TypeDocument $state): string => $state->label()),
                TextColumn::make('documentable_id')
                    ->label('Cible')
                    ->state(fn (Document $record): string => match (true) {
                        $record->documentable instanceof Contrat => 'Contrat #'.$record->documentable->getKey()
                            .' — '.$record->documentable->employe->prenom.' '.$record->documentable->employe->nom,
                        $record->documentable instanceof Employe => 'Salarié — '
                            .$record->documentable->prenom.' '.$record->documentable->nom,
                        default => 'Cible inconnue',
                    }),
                TextColumn::make('televersePar.name')->label('Téléversé par')->placeholder('Inconnu'),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(collect(TypeDocument::cases())
                        ->mapWithKeys(fn (TypeDocument $type): array => [$type->value => $type->label()])
                        ->all()),
                SelectFilter::make('documentable_type')
                    ->label('Nature de la cible')
                    ->options([
                        (new Contrat)->getMorphClass() => 'Contrat',
                        (new Employe)->getMorphClass() => 'Salarié',
                    ]),
            ])
            ->recordActions([
                Action::make('telecharger')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->authorize(fn (Document $record): bool => auth()->user()?->can('view', $record) ?? false)
                    ->action(fn (Document $record) => app(DocumentService::class)->telecharger($record)),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'documentable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        Contrat::class => ['employe'],
                    ]),
                    'televersePar',
                ]))
            ->defaultSort('created_at', 'desc');
    }
}
