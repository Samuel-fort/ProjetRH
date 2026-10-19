<?php

namespace App\Filament\Resources\Besoin\Postes;

use App\Filament\Resources\Besoin\Postes\Pages\CreatePoste;
use App\Filament\Resources\Besoin\Postes\Pages\EditPoste;
use App\Filament\Resources\Besoin\Postes\Pages\ListPostes;
use App\Models\Besoin\Poste;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Un poste existe independamment de tout employe (regle de conception du cours).
 * On ne supprime pas un poste : on le desactive (colonne "actif").
 */
class PosteResource extends Resource
{
    protected static ?string $model = Poste::class;

    protected static ?string $recordTitleAttribute = 'intitule';

    protected static ?string $modelLabel = 'poste';

    protected static ?string $pluralModelLabel = 'postes';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Bloc 1 — Besoin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('intitule')
                ->label('Intitulé')
                ->required()
                ->maxLength(255),
            Select::make('departement_id')
                ->label('Département')
                ->relationship('departement', 'nom')
                ->searchable()
                ->preload()
                ->required(),
            Textarea::make('description')
                ->label('Description')
                ->rows(3),
            Toggle::make('actif')
                ->label('Poste actif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('intitule')->label('Intitulé')->searchable()->sortable(),
                TextColumn::make('departement.nom')->label('Département')->sortable(),
                IconColumn::make('actif')->label('Actif')->boolean(),
            ])
            ->defaultSort('intitule')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostes::route('/'),
            'create' => CreatePoste::route('/create'),
            'edit' => EditPoste::route('/{record}/edit'),
        ];
    }
}
