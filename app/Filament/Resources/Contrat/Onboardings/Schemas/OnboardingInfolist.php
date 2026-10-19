<?php

namespace App\Filament\Resources\Contrat\Onboardings\Schemas;

use App\Enums\StatutOnboarding;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OnboardingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employe.prenom')
                    ->label('Salarié')
                    ->formatStateUsing(fn ($state, $record): string => "{$record->employe->prenom} {$record->employe->nom}"),
                TextEntry::make('contrat_id')
                    ->label('Contrat')
                    ->formatStateUsing(fn ($state, $record): string => 'Contrat '.$record->contrat_id),
                TextEntry::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (StatutOnboarding $state): string => $state->label())
                    ->color(fn (StatutOnboarding $state): string => $state === StatutOnboarding::Termine ? 'success' : 'warning'),
                TextEntry::make('date_fin')
                    ->label('Date de fin')
                    ->date('d/m/Y')
                    ->placeholder('En cours'),
            ]);
    }
}
