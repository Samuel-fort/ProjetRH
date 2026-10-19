<?php

namespace App\Filament\Resources\Contrat\Affectations\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AffectationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employe.prenom')
                    ->label('Salarié')
                    ->formatStateUsing(fn ($state, $record): string => "{$record->employe->prenom} {$record->employe->nom}"),
                TextColumn::make('poste.intitule')
                    ->label('Poste')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('departement.nom')
                    ->label('Département')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date_debut')
                    ->label('Date de début')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('date_fin')
                    ->label('Date de fin')
                    ->date('d/m/Y')
                    ->placeholder('En cours')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('date_fin')
                    ->label('En cours uniquement')
                    ->nullable()
                    ->trueLabel('Oui')
                    ->falseLabel('Non')
                    ->queries(
                        true: fn ($query) => $query->whereNull('date_fin'),
                        false: fn ($query) => $query->whereNotNull('date_fin'),
                    ),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['employe', 'poste', 'departement']))
            ->defaultSort('date_debut', 'desc');
    }
}
