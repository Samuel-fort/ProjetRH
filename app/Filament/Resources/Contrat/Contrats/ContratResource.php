<?php

namespace App\Filament\Resources\Contrat\Contrats;

use App\Filament\Resources\Contrat\Contrats\Pages\CreateContrat;
use App\Filament\Resources\Contrat\Contrats\Pages\EditContrat;
use App\Filament\Resources\Contrat\Contrats\Pages\ListContrats;
use App\Filament\Resources\Contrat\Contrats\Pages\ViewContrat;
use App\Filament\Resources\Contrat\Contrats\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Contrat\Contrats\RelationManagers\HistoriquesRelationManager;
use App\Filament\Resources\Contrat\Contrats\Schemas\ContratForm;
use App\Filament\Resources\Contrat\Contrats\Schemas\ContratInfolist;
use App\Filament\Resources\Contrat\Contrats\Tables\ContratsTable;
use App\Models\Contrat\Contrat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContratResource extends Resource
{
    protected static ?string $model = Contrat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Contrats et onboarding';

    protected static ?string $modelLabel = 'contrat';

    protected static ?string $pluralModelLabel = 'contrats';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Contrats';
    }

    public static function form(Schema $schema): Schema
    {
        return ContratForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContratInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContratsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
            HistoriquesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContrats::route('/'),
            'create' => CreateContrat::route('/create'),
            'view' => ViewContrat::route('/{record}'),
            'edit' => EditContrat::route('/{record}/edit'),
        ];
    }
}
