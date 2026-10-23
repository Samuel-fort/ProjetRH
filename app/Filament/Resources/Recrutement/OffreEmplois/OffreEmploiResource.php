<?php

namespace App\Filament\Resources\Recrutement\OffreEmplois;

use App\Enums\StatutOffre;
use App\Filament\Actions\SafeDeleteAction;
use App\Filament\Resources\Recrutement\OffreEmplois\Pages\CreateOffreEmploi;
use App\Filament\Resources\Recrutement\OffreEmplois\Pages\EditOffreEmploi;
use App\Filament\Resources\Recrutement\OffreEmplois\Pages\ListOffreEmplois;
use App\Models\Besoin\DemandeRecrutement;
use App\Models\Recrutement\OffreEmploi;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Écran "Offres d'emploi" (Bloc 2). */
class OffreEmploiResource extends Resource
{
    protected static ?string $model = OffreEmploi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Recrutement';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = "offre d'emploi";

    protected static ?string $pluralModelLabel = "offres d'emploi";

    protected static ?string $recordTitleAttribute = 'titre';

    /** Libellé lisible d'une demande de recrutement dans la liste déroulante. */
    public static function libelleDemande(DemandeRecrutement $demande): string
    {
        // `poste` est la relation du Bloc 1 (poste_id) ; on la lit seulement si elle existe.
        $poste = method_exists($demande, 'poste') ? $demande->poste?->intitule : null;

        return "Demande n°{$demande->getKey()}".($poste ? " — {$poste}" : '');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('demande_recrutement_id')
                ->label('Demande de recrutement validée')
                // Seules les demandes dont les 4 validations sont faites (statut recrutement_ouvert) sont proposées.
                ->options(fn () => DemandeRecrutement::query()
                    ->where('statut', 'recrutement_ouvert')
                    ->get()
                    ->mapWithKeys(fn ($d) => [$d->getKey() => self::libelleDemande($d)]))
                ->searchable()
                ->required()
                ->disabledOn('edit'), // on ne change pas l'origine d'une offre existante
            TextInput::make('titre')->label("Titre de l'offre")->required()->maxLength(255),
            Textarea::make('description')->label('Description')->rows(6)->columnSpanFull(),
            TextInput::make('statut')
                ->label('Statut')
                ->disabled()
                ->dehydrated(false) // le statut ne se modifie que par les boutons Publier / Clôturer
                ->formatStateUsing(fn ($state) => $state instanceof StatutOffre ? $state->getLabel() : $state)
                ->hiddenOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('titre')->label('Titre')->searchable()->sortable(),
                // badge() + enum HasLabel/HasColor = pastille colorée automatique.
                TextColumn::make('statut')->label('Statut')->badge(),
                TextColumn::make('date_publication')->label('Publiée le')->date('d/m/Y')->sortable(),
                TextColumn::make('date_cloture')->label('Clôturée le')->date('d/m/Y'),
                TextColumn::make('candidatures_count')->label('Candidatures')->counts('candidatures')->sortable(),
            ])
            ->filters([
                SelectFilter::make('statut')->label('Statut')->options(StatutOffre::class),
            ])
            ->recordActions([EditAction::make(), SafeDeleteAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOffreEmplois::route('/'),
            'create' => CreateOffreEmploi::route('/create'),
            'edit' => EditOffreEmploi::route('/{record}/edit'),
        ];
    }
}
