<?php

namespace App\Filament\Resources\Besoin\DemandeRecrutements;

use App\Enums\MotifDemande;
use App\Enums\StatutDemande;
use App\Filament\Resources\Besoin\DemandeRecrutements\Pages\CreateDemandeRecrutement;
use App\Filament\Resources\Besoin\DemandeRecrutements\Pages\EditDemandeRecrutement;
use App\Filament\Resources\Besoin\DemandeRecrutements\Pages\ListDemandeRecrutements;
use App\Filament\Resources\Besoin\DemandeRecrutements\Pages\ViewDemandeRecrutement;
use App\Filament\Resources\Besoin\DemandeRecrutements\RelationManagers\ValidationsRelationManager;
use App\Models\Besoin\DemandeRecrutement;
use App\Services\Besoin\DemandeRecrutementService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Ecrans de la demande de recrutement.
 * Les boutons Soumettre / Valider / Refuser n'ont AUCUNE regle metier :
 * ils appellent DemandeRecrutementService et la Policy (qui decide s'ils sont visibles).
 */
class DemandeRecrutementResource extends Resource
{
    protected static ?string $model = DemandeRecrutement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'demande de recrutement';

    protected static ?string $pluralModelLabel = 'demandes de recrutement';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'Besoin';
    }

    /** Formulaire de creation / modification (le statut n'est jamais modifiable ici). */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('poste_id')
                ->label('Poste')
                ->relationship('poste', 'intitule')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('nombre')
                ->label('Nombre de personnes')
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required(),
            Select::make('motif')
                ->label('Motif')
                ->options(MotifDemande::options())
                // Le modele renvoie un enum : on donne sa valeur texte a la liste deroulante.
                ->formatStateUsing(fn ($state) => $state instanceof BackedEnum ? $state->value : $state)
                ->required(),
            TextInput::make('budget')
                ->label('Budget (Ar)')
                ->numeric()
                ->minValue(0),
            DatePicker::make('date_souhaitee')
                ->label('Date souhaitée'),
        ]);
    }

    /** Page de consultation d'une demande. */
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('poste.intitule')->label('Poste'),
            TextEntry::make('departement.nom')->label('Département'),
            TextEntry::make('demandeur.name')->label('Demandeur'),
            TextEntry::make('nombre')->label('Nombre de personnes'),
            TextEntry::make('motif')
                ->label('Motif')
                ->state(fn (DemandeRecrutement $record) => $record->motif->libelle()),
            TextEntry::make('budget')
                ->label('Budget')
                ->state(fn (DemandeRecrutement $record) => $record->budget === null
                    ? '—'
                    : number_format((float) $record->budget, 0, ',', ' ').' Ar'),
            TextEntry::make('date_souhaitee')
                ->label('Date souhaitée')
                ->state(fn (DemandeRecrutement $record) => $record->date_souhaitee?->format('d/m/Y') ?? '—'),
            TextEntry::make('statut')
                ->label('Statut')
                ->badge()
                ->state(fn (DemandeRecrutement $record) => $record->statut->libelle())
                ->color(fn (DemandeRecrutement $record) => $record->statut->couleur()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('N°')->sortable(),
                TextColumn::make('poste.intitule')->label('Poste')->searchable(),
                TextColumn::make('departement.nom')->label('Département'),
                TextColumn::make('demandeur.name')->label('Demandeur'),
                TextColumn::make('nombre')->label('Nb'),
                TextColumn::make('motif')
                    ->label('Motif')
                    ->state(fn (DemandeRecrutement $record) => $record->motif->libelle()),
                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->state(fn (DemandeRecrutement $record) => $record->statut->libelle())
                    ->color(fn (DemandeRecrutement $record) => $record->statut->couleur()),
                TextColumn::make('progression')
                    ->label('Validations')
                    ->state(fn (DemandeRecrutement $record) => self::progression($record)),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(StatutDemande::options()),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ...self::actionsWorkflow(),
            ]);
    }

    /** Les trois boutons du workflow. Visibles selon la Policy. */
    protected static function actionsWorkflow(): array
    {
        return [
            Action::make('soumettre')
                ->label('Soumettre')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalDescription('La demande sera envoyée pour validation (Manager, RH, Finance, Direction).')
                ->visible(fn (DemandeRecrutement $record): bool => (bool) auth()->user()?->can('soumettre', $record))
                ->action(fn (DemandeRecrutement $record) => self::executer(
                    fn () => app(DemandeRecrutementService::class)->soumettre($record),
                    'Demande soumise pour validation.',
                )),

            Action::make('valider')
                ->label('Valider')
                ->icon('heroicon-o-check')
                ->color('success')
                ->schema([
                    Textarea::make('commentaire')->label('Commentaire (facultatif)'),
                ])
                ->visible(fn (DemandeRecrutement $record): bool => (bool) auth()->user()?->can('valider', $record))
                ->action(fn (DemandeRecrutement $record, array $data) => self::executer(
                    fn () => app(DemandeRecrutementService::class)->valider($record, auth()->user(), $data['commentaire'] ?? null),
                    'Étape validée.',
                )),

            Action::make('refuser')
                ->label('Refuser')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->schema([
                    Textarea::make('commentaire')->label('Motif du refus')->required(),
                ])
                ->visible(fn (DemandeRecrutement $record): bool => (bool) auth()->user()?->can('refuser', $record))
                ->action(fn (DemandeRecrutement $record, array $data) => self::executer(
                    fn () => app(DemandeRecrutementService::class)->refuser($record, auth()->user(), $data['commentaire']),
                    'Demande refusée.',
                )),
        ];
    }

    /** Execute une operation du service et affiche le resultat (ou le message d'erreur de la regle). */
    protected static function executer(callable $operation, string $messageSucces): void
    {
        try {
            $operation();

            Notification::make()->title($messageSucces)->success()->send();
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    /** Texte de la colonne "Validations" : "2/4 validées · étape : Finance". */
    protected static function progression(DemandeRecrutement $demande): string
    {
        $total = $demande->validations()->count();

        if ($total === 0) {
            return '—';
        }

        $validees = $demande->validations()->where('statut', 'validee')->count();
        $courante = $demande->etapeCourante();

        return "{$validees}/{$total} validées".($courante ? ' · étape : '.$courante->etape->libelle() : '');
    }

    public static function getRelations(): array
    {
        return [
            ValidationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDemandeRecrutements::route('/'),
            'create' => CreateDemandeRecrutement::route('/create'),
            'view' => ViewDemandeRecrutement::route('/{record}'),
            'edit' => EditDemandeRecrutement::route('/{record}/edit'),
        ];
    }
}
