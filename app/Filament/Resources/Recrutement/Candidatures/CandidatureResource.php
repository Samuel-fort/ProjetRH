<?php

namespace App\Filament\Resources\Recrutement\Candidatures;

use App\Enums\StatutCandidature;
use App\Filament\Resources\Recrutement\Candidatures\Pages\ListCandidatures;
use App\Filament\Resources\Recrutement\Candidatures\Pages\ViewCandidature;
use App\Models\Recrutement\Candidature;
use App\Services\Recrutement\PreselectionCvService;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Écran "Candidatures" : le suivi du pipeline.
 * Pas de formulaire de modification : un statut ne se change JAMAIS à la main,
 * uniquement par les boutons de la page de détail (qui passent par CandidatureService).
 */
class CandidatureResource extends Resource
{
    protected static ?string $model = Candidature::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Recrutement';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'candidature';

    protected static ?string $pluralModelLabel = 'candidatures';

    /** Pas de création "brute" : on passe par l'action « Enregistrer une candidature » (service). */
    public static function canCreate(): bool
    {
        return false;
    }

    /** L'infolist = la fiche en lecture seule affichée par la page de détail. */
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Candidature')->columns(3)->schema([
                TextEntry::make('candidat.nom_complet')->label('Candidat'),
                TextEntry::make('candidat.email')->label('Email'),
                TextEntry::make('candidat.telephone')->label('Téléphone')->placeholder('—'),
                TextEntry::make('offreEmploi.titre')->label('Offre'),
                TextEntry::make('statut')->label('Statut')->badge(),
                TextEntry::make('date_candidature')->label('Reçue le')->date('d/m/Y'),
                TextEntry::make('moyenne')
                    ->label('Note moyenne /20')
                    ->state(fn (Candidature $record) => $record->evaluations()->avg('note'))
                    ->numeric(decimalPlaces: 1)
                    ->placeholder('—'),
                TextEntry::make('candidat.cv_path')
                    ->label('CV')
                    ->placeholder('Aucun CV')
                    ->url(fn (Candidature $record) => $record->candidat->cv_path ? asset('storage/'.$record->candidat->cv_path) : null)
                    ->openUrlInNewTab(),
            ]),

            Section::make('Présélection du CV')->schema([
                TextEntry::make('analyse_preselection.recommandation')->label('Recommandation')->badge(),
                TextEntry::make('analyse_preselection.score')->label('Score /100'),
                TextEntry::make('analyse_preselection.confiance')->label('Confiance'),
                TextEntry::make('analyse_preselection.resume')->label('Résumé')->columnSpanFull(),
                RepeatableEntry::make('analyse_preselection.criteres')->label('Critères')->columns(4)->schema([
                    TextEntry::make('critere')->label('Critère'),
                    TextEntry::make('type')->label('Type'),
                    TextEntry::make('statut')->label('Statut'),
                    TextEntry::make('preuve')->label('Preuve'),
                ]),
                TextEntry::make('analyse_preselection.points_forts')
                    ->label('Points forts')
                    ->formatStateUsing(fn (mixed $state): string => self::formatListeAnalyse($state)),
                TextEntry::make('analyse_preselection.points_de_vigilance')
                    ->label('Points de vigilance')
                    ->formatStateUsing(fn (mixed $state): string => self::formatListeAnalyse($state)),
                TextEntry::make('analyse_preselection.questions_entretien')
                    ->label('Questions d’entretien')
                    ->formatStateUsing(fn (mixed $state): string => self::formatListeAnalyse($state)),
                TextEntry::make('analyse_preselection.alertes')
                    ->label('Alertes')
                    ->formatStateUsing(fn (mixed $state): string => self::formatListeAnalyse($state))
                    ->placeholder('Aucune'),
            ])->collapsible(),

            Section::make('Entretiens et tests')->schema([
                RepeatableEntry::make('entretiens')->hiddenLabel()->placeholder('Aucune étape planifiée')->columns(4)->schema([
                    TextEntry::make('type')->label('Étape')->badge(),
                    TextEntry::make('date_heure')->label('Date')->dateTime('d/m/Y H:i'),
                    TextEntry::make('lieu')->label('Lieu')->placeholder('—'),
                    TextEntry::make('intervenant.name')->label('Intervenant')->placeholder('—'),
                ]),
            ]),

            Section::make('Évaluations')->schema([
                RepeatableEntry::make('evaluations')->hiddenLabel()->placeholder('Aucune évaluation')->columns(4)->schema([
                    TextEntry::make('evaluateur.name')->label('Évaluateur'),
                    TextEntry::make('note')->label('Note /20')->numeric(decimalPlaces: 1),
                    TextEntry::make('commentaire')->label('Commentaire')->placeholder('—')->columnSpan(2),
                ]),
            ]),

            // Traçabilité : alimentée automatiquement par CandidatureObserver.
            Section::make('Historique des statuts')->collapsed()->schema([
                RepeatableEntry::make('historiques')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('ancien_statut')->label('De')->badge()->placeholder('Création'),
                    TextEntry::make('nouveau_statut')->label('Vers')->badge(),
                    TextEntry::make('user.name')->label('Par')->placeholder('Système'),
                    TextEntry::make('created_at')->label('Le')->dateTime('d/m/Y H:i'),
                ]),
            ]),
        ]);
    }

    private static function formatListeAnalyse(mixed $state): string
    {
        if (is_string($state)) {
            $decoded = json_decode($state, true);

            if (is_array($decoded)) {
                $state = $decoded;
            }
        }

        return is_array($state) ? implode("\n", $state) : (string) ($state ?? '');
    }

    public static function table(Table $table): Table
    {
        return $table
            // with() charge candidat et offre en une fois (évite une requête par ligne).
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['candidat', 'offreEmploi']))
            ->columns([
                TextColumn::make('candidat.nom')
                    ->label('Candidat')
                    ->state(fn (Candidature $record) => $record->candidat->nom_complet)
                    ->searchable(['nom', 'prenom']),
                TextColumn::make('offreEmploi.titre')->label('Offre')->searchable(),
                TextColumn::make('statut')->label('Statut')->badge(),
                TextColumn::make('evaluations_avg_note')
                    ->label('Moyenne /20')
                    ->avg('evaluations', 'note')
                    ->numeric(decimalPlaces: 1)
                    ->placeholder('—'),
                TextColumn::make('date_candidature')->label('Reçue le')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('statut')->label('Statut')->options(StatutCandidature::class),
                SelectFilter::make('offre_emploi_id')->label('Offre')->relationship('offreEmploi', 'titre'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('analyser_cv')
                        ->label('Analyser les CV sélectionnés avec l’IA')
                        ->icon('heroicon-o-sparkles')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $rapport = app(PreselectionCvService::class)->analyserPlusieurs($records);
                            $message = "{$rapport['analysees']} CV analysé(s).";

                            if ($rapport['erreurs'] !== []) {
                                $message .= ' '.count($rapport['erreurs']).' erreur(s) : '.implode(' | ', $rapport['erreurs']);
                            }

                            Notification::make()
                                ->title($message)
                                ->color($rapport['erreurs'] === [] ? 'success' : 'warning')
                                ->send();
                        }),
                ]),
            ])
            ->recordActions([ViewAction::make()->label('Ouvrir')])
            ->defaultSort('date_candidature', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCandidatures::route('/'),
            'view' => ViewCandidature::route('/{record}'),
        ];
    }
}
