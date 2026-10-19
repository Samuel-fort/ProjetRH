<?php

namespace App\Filament\Resources\Embauche\PropositionEmbauches;

use App\Enums\StatutCandidature;
use App\Enums\StatutProposition;
use App\Filament\Resources\Embauche\PropositionEmbauches\Pages\CreatePropositionEmbauche;
use App\Filament\Resources\Embauche\PropositionEmbauches\Pages\EditPropositionEmbauche;
use App\Filament\Resources\Embauche\PropositionEmbauches\Pages\ListPropositionEmbauches;
use App\Models\Embauche\PropositionEmbauche;
use App\Models\Recrutement\Candidature;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

/** Écran "Propositions d'embauche" (Bloc 3). */
class PropositionEmbaucheResource extends Resource
{
    protected static ?string $model = PropositionEmbauche::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Embauche';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = "proposition d'embauche";

    protected static ?string $pluralModelLabel = "propositions d'embauche";

    /** Une proposition n'est modifiable que tant qu'elle est en brouillon. */
    public static function estVerrouillee(?PropositionEmbauche $record): bool
    {
        return $record !== null && $record->statut !== StatutProposition::Brouillon;
    }

    public static function form(Schema $schema): Schema
    {
        $verrou = fn (?PropositionEmbauche $record) => self::estVerrouillee($record);

        return $schema->components([
            Select::make('candidature_id')
                ->label('Candidat retenu')
                // Seules les candidatures RETENUES sans proposition en cours sont proposées.
                ->options(function () {
                    $dejaTraitees = PropositionEmbauche::whereIn('statut', ['brouillon', 'envoyee', 'acceptee'])->pluck('candidature_id');

                    return Candidature::with(['candidat', 'offreEmploi'])
                        ->where('statut', StatutCandidature::Retenue)
                        ->whereNotIn('id', $dejaTraitees)
                        ->get()
                        ->mapWithKeys(fn ($c) => [$c->id => "{$c->candidat->nom_complet} — {$c->offreEmploi->titre}"]);
                })
                ->searchable()
                ->required()
                ->disabledOn('edit'),
            TextInput::make('salaire')->label('Salaire mensuel brut')->numeric()->minValue(1)->suffix('Ar')
                ->required()->disabled($verrou),
            TextInput::make('horaires')->label('Horaires')->placeholder('Lundi au vendredi, 8h-17h')
                ->required()->maxLength(255)->disabled($verrou),
            TextInput::make('lieu_travail')->label('Lieu de travail')->required()->maxLength(255)->disabled($verrou),
            DatePicker::make('date_debut')->label('Date de début')->minDate(now()->startOfDay())
                ->required()->disabled($verrou),
            TextInput::make('statut')->label('Statut')->disabled()->dehydrated(false)
                ->formatStateUsing(fn ($state) => $state instanceof StatutProposition ? $state->getLabel() : $state)
                ->hiddenOn('create'),
            TextEntry::make('matricule')->label("Matricule de l'employé")
                ->state(fn (?PropositionEmbauche $record) => $record?->employe?->matricule)
                ->visible(fn (?PropositionEmbauche $record) => $record?->employe_id !== null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['candidature.candidat', 'candidature.offreEmploi', 'employe']))
            ->columns([
                TextColumn::make('candidat')->label('Candidat')
                    ->state(fn (PropositionEmbauche $record) => $record->candidature->candidat->nom_complet),
                TextColumn::make('candidature.offreEmploi.titre')->label('Poste'),
                TextColumn::make('salaire')->label('Salaire')->numeric(0, ',', ' ')->suffix(' Ar')->sortable(),
                TextColumn::make('date_debut')->label('Début')->date('d/m/Y')->sortable(),
                TextColumn::make('statut')->label('Statut')->badge(),
                TextColumn::make('employe.matricule')->label('Matricule')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('statut')->label('Statut')->options(StatutProposition::class),
            ])
            ->recordActions([EditAction::make()->label('Ouvrir')])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPropositionEmbauches::route('/'),
            'create' => CreatePropositionEmbauche::route('/create'),
            'edit' => EditPropositionEmbauche::route('/{record}/edit'),
        ];
    }
}
