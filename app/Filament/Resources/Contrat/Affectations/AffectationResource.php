<?php

namespace App\Filament\Resources\Contrat\Affectations;

use App\Filament\Resources\Contrat\Affectations\Pages\CreateAffectation;
use App\Filament\Resources\Contrat\Affectations\Pages\ListAffectations;
use App\Filament\Resources\Contrat\Affectations\Schemas\AffectationForm;
use App\Filament\Resources\Contrat\Affectations\Schemas\AffectationInfolist;
use App\Filament\Resources\Contrat\Affectations\Tables\AffectationsTable;
use App\Models\Contrat\Affectation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AffectationResource extends Resource
{
    protected static ?string $model = Affectation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Contrats et onboarding';

    protected static ?string $modelLabel = 'affectation';

    protected static ?string $pluralModelLabel = 'affectations';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Affectations';
    }

    public static function form(Schema $schema): Schema
    {
        return AffectationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AffectationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AffectationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffectations::route('/'),
            'create' => CreateAffectation::route('/create'),
        ];
    }
}
