<?php

namespace App\Filament\Resources\Contrat\Onboardings\RelationManagers;

use App\Enums\StatutTache;
use App\Models\Contrat\OnboardingTache;
use App\Models\User;
use App\Services\Contrat\OnboardingService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TachesRelationManager extends RelationManager
{
    protected static string $relationship = 'taches';

    protected static ?string $title = 'Tâches';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('libelle')->label('Tâche')->searchable(),
                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (StatutTache $state): string => $state->label())
                    ->color(fn (StatutTache $state): string => match ($state) {
                        StatutTache::AFaire => 'gray',
                        StatutTache::EnCours => 'warning',
                        StatutTache::Fait => 'success',
                    }),
                TextColumn::make('responsable.name')->label('Responsable')->placeholder('Non assigné'),
                TextColumn::make('date_echeance')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->placeholder('Non définie')
                    ->color(fn (OnboardingTache $record): ?string => $record->date_echeance !== null
                        && $record->date_echeance->isBefore(today())
                        && $record->statut !== StatutTache::Fait
                            ? 'danger'
                            : null),
                TextColumn::make('date_realisation')->label('Réalisation')->dateTime('d/m/Y H:i')->placeholder('Non réalisée'),
            ])
            ->recordActions([
                Action::make('marquerCommeFaite')
                    ->label('Marquer comme faite')
                    ->color('success')
                    ->visible(fn (OnboardingTache $record): bool => $record->statut !== StatutTache::Fait)
                    ->authorize(fn (OnboardingTache $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(fn (OnboardingTache $record) => $this->changerStatut($record, StatutTache::Fait))
                    ->successNotificationTitle('Tâche terminée'),
                Action::make('rouvrir')
                    ->label('Rouvrir')
                    ->color('warning')
                    ->visible(fn (OnboardingTache $record): bool => $record->statut === StatutTache::Fait)
                    ->authorize(fn (OnboardingTache $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(fn (OnboardingTache $record) => $this->changerStatut($record, StatutTache::AFaire))
                    ->successNotificationTitle('Tâche rouverte'),
                Action::make('assigner')
                    ->label('Assigner')
                    ->schema([
                        Select::make('responsable_id')
                            ->label('Responsable')
                            ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->nullable(),
                        DatePicker::make('date_echeance')
                            ->label('Échéance'),
                    ])
                    ->authorize(fn (OnboardingTache $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(function (OnboardingTache $record, array $data): void {
                        app(OnboardingService::class)->assignerTache(
                            $record,
                            filled($data['responsable_id'] ?? null) ? (int) $data['responsable_id'] : null,
                            filled($data['date_echeance'] ?? null)
                                ? CarbonImmutable::parse($data['date_echeance'])
                                : null
                        );

                        $this->getOwnerRecord()->refresh();
                        $this->dispatch('onboarding-updated');
                    })
                    ->successNotificationTitle('Tâche assignée'),
            ])
            ->headerActions([])
            ->toolbarActions([])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('responsable')
                ->orderBy('id'));
    }

    private function changerStatut(OnboardingTache $tache, StatutTache $statut): void
    {
        app(OnboardingService::class)->changerStatutTache($tache, $statut);

        $this->getOwnerRecord()->refresh();
        $this->dispatch('onboarding-updated');
    }
}
