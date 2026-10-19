<?php

namespace App\Filament\Resources\Contrat\Contrats\RelationManagers;

use App\Enums\StatutContrat;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HistoriquesRelationManager extends RelationManager
{
    protected static string $relationship = 'historiques';

    protected static ?string $title = 'Historique des statuts';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
                TextColumn::make('ancien_statut')
                    ->label('Ancien statut')
                    ->formatStateUsing(fn (?StatutContrat $state): string => $state?->label() ?? 'Création'),
                TextColumn::make('nouveau_statut')
                    ->label('Nouveau statut')
                    ->formatStateUsing(fn (StatutContrat $state): string => $state->label()),
                TextColumn::make('utilisateur.name')->label('Utilisateur')->placeholder('Système'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->headerActions([])
            ->toolbarActions([])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('utilisateur'));
    }
}
