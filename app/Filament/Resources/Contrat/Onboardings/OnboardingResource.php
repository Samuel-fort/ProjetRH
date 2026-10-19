<?php

namespace App\Filament\Resources\Contrat\Onboardings;

use App\Filament\Resources\Contrat\Onboardings\Pages\ListOnboardings;
use App\Filament\Resources\Contrat\Onboardings\Pages\ViewOnboarding;
use App\Filament\Resources\Contrat\Onboardings\Schemas\OnboardingForm;
use App\Filament\Resources\Contrat\Onboardings\Schemas\OnboardingInfolist;
use App\Filament\Resources\Contrat\Onboardings\Tables\OnboardingsTable;
use App\Models\Contrat\Onboarding;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OnboardingResource extends Resource
{
    protected static ?string $model = Onboarding::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Contrats et onboarding';

    protected static ?string $modelLabel = 'onboarding';

    protected static ?string $pluralModelLabel = 'onboardings';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Onboardings';
    }

    public static function form(Schema $schema): Schema
    {
        return OnboardingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OnboardingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OnboardingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TachesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOnboardings::route('/'),
            'view' => ViewOnboarding::route('/{record}'),
        ];
    }
}
