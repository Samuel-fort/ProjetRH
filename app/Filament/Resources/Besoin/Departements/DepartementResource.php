<?php

namespace App\Filament\Resources\Besoin\Departements;

use App\Filament\Resources\Besoin\Departements\Pages\CreateDepartement;
use App\Filament\Resources\Besoin\Departements\Pages\EditDepartement;
use App\Filament\Resources\Besoin\Departements\Pages\ListDepartements;
use App\Models\Besoin\Departement;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Une "Resource" Filament = les ecrans d'un modele : liste, creation, modification.
 * form()  : le formulaire de creation / modification.
 * table() : la liste.
 * Pas de bouton Supprimer ici : un departement utilise par des postes ne peut
 * pas etre supprime (cle etrangere restrictOnDelete).
 */
class DepartementResource extends Resource
{
    protected static ?string $model = Departement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'nom';

    protected static ?string $modelLabel = 'département';

    protected static ?string $pluralModelLabel = 'départements';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Besoin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')
                ->label('Nom')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('code')
                ->label('Code')
                ->maxLength(20)
                ->unique(ignoreRecord: true),
            Select::make('parent_id')
                ->label('Département parent')
                ->relationship('parent', 'nom')
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Nom')->searchable()->sortable(),
                TextColumn::make('code')->label('Code')->searchable(),
                TextColumn::make('parent.nom')->label('Parent')->placeholder('—'),
                TextColumn::make('postes_count')->label('Postes')->counts('postes'),
            ])
            ->defaultSort('nom')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartements::route('/'),
            'create' => CreateDepartement::route('/create'),
            'edit' => EditDepartement::route('/{record}/edit'),
        ];
    }
}
