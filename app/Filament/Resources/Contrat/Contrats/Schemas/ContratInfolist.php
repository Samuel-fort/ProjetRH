<?php

namespace App\Filament\Resources\Contrat\Contrats\Schemas;

use App\Enums\StatutContrat;
use App\Enums\TypeContrat;
use App\Filament\Resources\Contrat\Contrats\ContratResource;
use App\Models\Contrat\Contrat;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContratInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employe.prenom')
                    ->label('Salarié')
                    ->formatStateUsing(fn ($state, Contrat $record): string => "{$record->employe->prenom} {$record->employe->nom}"),
                TextEntry::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (TypeContrat $state): string => $state->label()),
                TextEntry::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (StatutContrat $state): string => $state->label()),
                TextEntry::make('date_debut')->label('Date de début')->date('d/m/Y'),
                TextEntry::make('date_fin')->label('Date de fin')->date('d/m/Y')->placeholder('Indéterminée'),
                TextEntry::make('date_signature')->label('Date de signature')->date('d/m/Y')->placeholder('Non signé'),
                TextEntry::make('date_validation')->label('Date de validation')->date('d/m/Y')->placeholder('Non validé'),
                TextEntry::make('salaire')->label('Salaire')->money('MGA', locale: 'fr'),
                TextEntry::make('horaires')->label('Horaires'),
                TextEntry::make('lieu_travail')->label('Lieu de travail'),
                TextEntry::make('precedent_id')
                    ->label('Contrat précédent')
                    ->formatStateUsing(fn ($state): string => $state === null ? 'Aucun' : 'Contrat #'.$state)
                    ->url(fn (Contrat $record): ?string => $record->precedent === null
                        ? null
                        : ContratResource::getUrl('view', ['record' => $record->precedent])),
                TextEntry::make('suivant.id')
                    ->label('Contrat suivant')
                    ->formatStateUsing(fn ($state): string => $state === null ? 'Aucun' : 'Contrat #'.$state)
                    ->url(fn (Contrat $record): ?string => $record->suivant === null
                        ? null
                        : ContratResource::getUrl('view', ['record' => $record->suivant])),
            ]);
    }
}
