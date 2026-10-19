<?php

namespace App\Filament\Resources\Contrat\Documents;

use App\Filament\Resources\Contrat\Documents\Pages\CreateDocument;
use App\Filament\Resources\Contrat\Documents\Pages\ListDocuments;
use App\Filament\Resources\Contrat\Documents\Schemas\DocumentForm;
use App\Filament\Resources\Contrat\Documents\Schemas\DocumentInfolist;
use App\Filament\Resources\Contrat\Documents\Tables\DocumentsTable;
use App\Models\Contrat\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Contrats et onboarding';

    protected static ?string $modelLabel = 'document';

    protected static ?string $pluralModelLabel = 'documents';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return 'Documents';
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
        ];
    }
}
