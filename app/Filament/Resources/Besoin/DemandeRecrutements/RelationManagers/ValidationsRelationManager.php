<?php

namespace App\Filament\Resources\Besoin\DemandeRecrutements\RelationManagers;

use App\Models\Besoin\Validation;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Tableau des 4 etapes de validation, affiche sous la demande.
 * Il ne contient aucune action : les decisions passent par les boutons Valider / Refuser.
 */
class ValidationsRelationManager extends RelationManager
{
    protected static string $relationship = 'validations';

    protected static ?string $title = 'Progression des validations';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('etape')
                    ->label('Étape')
                    ->state(fn (Validation $record) => $record->etape->libelle()),
                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->state(fn (Validation $record) => $record->statut->libelle())
                    ->color(fn (Validation $record) => $record->statut->couleur()),
                TextColumn::make('validateur.name')->label('Validateur')->placeholder('—'),
                TextColumn::make('commentaire')->label('Commentaire')->placeholder('—')->wrap(),
                TextColumn::make('date_decision')->label('Décision le')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->defaultSort('id');
    }
}
