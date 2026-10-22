<?php

namespace App\Filament\Resources\Recrutement\Candidats;

use App\Filament\Resources\Recrutement\Candidats\Pages\CreateCandidat;
use App\Filament\Resources\Recrutement\Candidats\Pages\EditCandidat;
use App\Filament\Resources\Recrutement\Candidats\Pages\ListCandidats;
use App\Models\Recrutement\Candidat;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Écran "Candidats" (Bloc 2).
 * Une "Resource" Filament = un écran d'administration complet pour un modèle :
 * liste, création, modification. Le formulaire et le tableau sont définis ici,
 * les pages (dossier Pages/) ne servent qu'à les afficher.
 */
class CandidatResource extends Resource
{
    protected static ?string $model = Candidat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Recrutement';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'candidat';

    protected static ?string $pluralModelLabel = 'candidats';

    protected static ?string $recordTitleAttribute = 'nom';

    /** Le formulaire (création et modification). */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')->label('Nom')->required()->maxLength(255),
            TextInput::make('prenom')->label('Prénom')->required()->maxLength(255),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255)
                // Un email = un candidat : on refuse un doublon (sauf pour le candidat en cours de modification).
                ->unique(ignoreRecord: true)
                // On enregistre toujours l'email en minuscules, comme le fait CandidatureService.
                ->dehydrateStateUsing(fn (?string $state) => mb_strtolower(trim((string) $state))),
            TextInput::make('telephone')->label('Téléphone')->tel()->maxLength(255),
            FileUpload::make('cv_path')
                ->label('CV (PDF)')
                ->disk('public')          // nécessite `php artisan storage:link` pour ouvrir le fichier
                ->directory('cvs')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(5120),          // 5 Mo
            Textarea::make('cv_texte')
                ->label('Texte du CV')
                ->rows(8)
                ->columnSpanFull(),
        ]);
    }

    /** Le tableau de la liste. */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Nom')->searchable()->sortable(),
                TextColumn::make('prenom')->label('Prénom')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('telephone')->label('Téléphone'),
                // counts('candidatures') ajoute le nombre de candidatures sans requête par ligne.
                TextColumn::make('candidatures_count')->label('Candidatures')->counts('candidatures')->sortable(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('nom');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCandidats::route('/'),
            'create' => CreateCandidat::route('/create'),
            'edit' => EditCandidat::route('/{record}/edit'),
        ];
    }
}
