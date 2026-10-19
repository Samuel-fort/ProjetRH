<?php

namespace App\Filament\Resources\Contrat\Contrats\Tables;

use App\Enums\StatutContrat;
use App\Enums\TypeContrat;
use App\Filament\Resources\Contrat\Contrats\ContratResource;
use App\Models\Embauche\Employe;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContratsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employe.prenom')
                    ->label('Salarié')
                    ->formatStateUsing(fn ($state, $record): string => "{$record->employe->prenom} {$record->employe->nom}")
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'employe',
                        fn (Builder $employes): Builder => $employes
                            ->whereRaw('LOWER(nom) LIKE ?', ['%'.mb_strtolower($search).'%'])
                            ->orWhereRaw('LOWER(prenom) LIKE ?', ['%'.mb_strtolower($search).'%'])
                            ->orWhereRaw('LOWER(COALESCE(matricule, \'\')) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    )),
                TextColumn::make('employe.matricule')
                    ->label('Matricule')
                    ->formatStateUsing(fn (?string $state): string => $state ?? 'Non attribué')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (TypeContrat $state): string => $state->label()),
                TextColumn::make('date_debut')
                    ->label('Date de début')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('date_fin')
                    ->label('Date de fin')
                    ->date('d/m/Y')
                    ->placeholder('Indéterminée')
                    ->sortable(),
                TextColumn::make('salaire')
                    ->label('Salaire')
                    ->money('MGA', locale: 'fr')
                    ->sortable(),
                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (StatutContrat $state): string => $state->label())
                    ->color(fn (StatutContrat $state): string => match ($state) {
                        StatutContrat::Cree => 'gray',
                        StatutContrat::Signe => 'warning',
                        StatutContrat::Valide => 'success',
                    }),
                TextColumn::make('precedent.id')
                    ->label('Renouvellement')
                    ->formatStateUsing(fn ($state): string => $state === null ? 'Non' : 'Oui'),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(collect(StatutContrat::cases())
                        ->mapWithKeys(fn (StatutContrat $statut): array => [
                            $statut->value => $statut->label(),
                        ])
                        ->all()),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(collect(TypeContrat::cases())
                        ->mapWithKeys(fn (TypeContrat $type): array => [$type->value => $type->label()])
                        ->all()),
                SelectFilter::make('employe_id')
                    ->label('Salarié')
                    ->relationship('employe', 'nom')
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn (Employe $record): string => "{$record->prenom} {$record->nom} (".($record->matricule ?? 'Non attribué').')'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->recordUrl(fn ($record): string => ContratResource::getUrl('view', ['record' => $record]))
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['employe', 'precedent']))
            ->defaultSort('date_debut', 'desc');
    }
}
