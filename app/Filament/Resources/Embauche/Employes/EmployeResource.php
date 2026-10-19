<?php

namespace App\Filament\Resources\Embauche\Employes;

use App\Enums\StatutEmploye;
use App\Filament\Resources\Embauche\Employes\Pages\CreateEmploye;
use App\Filament\Resources\Embauche\Employes\Pages\EditEmploye;
use App\Filament\Resources\Embauche\Employes\Pages\ListEmployes;
use App\Models\Embauche\Employe;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Écran "Employés" (fiche salarié).
 * Les employés sont créés automatiquement à l'acceptation d'une proposition ;
 * la création manuelle sert aux imports (employé qui n'a jamais été candidat).
 * Les liens vers contrats et affectations seront ajoutés quand le Bloc 4 sera fusionné.
 */
class EmployeResource extends Resource
{
    protected static ?string $model = Employe::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Embauche';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'employé';

    protected static ?string $pluralModelLabel = 'employés';

    protected static ?string $recordTitleAttribute = 'nom';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')->label('Nom')->required()->maxLength(255),
            TextInput::make('prenom')->label('Prénom')->required()->maxLength(255),
            TextInput::make('email')->label('Email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
            Select::make('statut')->label('Statut')->options(StatutEmploye::class)
                ->default(StatutEmploye::EnIntegration)->required()->native(false),
            // Le matricule ne se saisit jamais à la main : il est généré (MatriculeService).
            TextInput::make('matricule')->label('Matricule')->disabled()->dehydrated(false)
                ->placeholder('Non attribué')->hiddenOn('create'),
            TextEntry::make('origine')->label('Candidat d\'origine')
                ->state(fn (?Employe $record) => $record?->candidat?->nom_complet ?? 'Employé importé')
                ->hiddenOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('matricule')->label('Matricule')->searchable()->sortable()->placeholder('—'),
                TextColumn::make('nom')->label('Nom')->searchable()->sortable(),
                TextColumn::make('prenom')->label('Prénom')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('statut')->label('Statut')->badge(),
            ])
            ->filters([
                SelectFilter::make('statut')->label('Statut')->options(StatutEmploye::class),
            ])
            ->recordActions([EditAction::make()->label('Ouvrir')])
            ->defaultSort('matricule', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployes::route('/'),
            'create' => CreateEmploye::route('/create'),
            'edit' => EditEmploye::route('/{record}/edit'),
        ];
    }
}
