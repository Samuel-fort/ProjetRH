<?php

namespace App\Filament\Resources\Contrat\Onboardings\Tables;

use App\Enums\StatutOnboarding;
use App\Filament\Resources\Contrat\Contrats\ContratResource;
use App\Models\Contrat\Onboarding;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OnboardingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employe.prenom')
                    ->label('Salarié')
                    ->formatStateUsing(fn ($state, $record): string => "{$record->employe->prenom} {$record->employe->nom}")
                    ->searchable(),
                TextColumn::make('contrat_id')
                    ->label('Contrat')
                    ->formatStateUsing(fn ($state): string => 'Contrat #'.$state)
                    ->url(fn (Onboarding $record): string => ContratResource::getUrl('view', ['record' => $record->contrat])),
                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (StatutOnboarding $state): string => $state->label())
                    ->color(fn (StatutOnboarding $state): string => $state === StatutOnboarding::Termine ? 'success' : 'warning'),
                TextColumn::make('progression')
                    ->label('Progression')
                    ->state(function (Onboarding $record): string {
                        $progression = $record->progression();

                        return $progression['faites'].' / '.$progression['total'];
                    }),
                TextColumn::make('date_fin')
                    ->label('Date de fin')
                    ->date('d/m/Y')
                    ->placeholder('En cours')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(collect(StatutOnboarding::cases())
                        ->mapWithKeys(fn (StatutOnboarding $statut): array => [
                            $statut->value => $statut->label(),
                        ])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['employe', 'contrat', 'taches']))
            ->defaultSort('created_at', 'desc');
    }
}
