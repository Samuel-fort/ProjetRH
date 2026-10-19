<?php

namespace App\Filament\Resources\Contrat\Contrats\Schemas;

use App\Enums\TypeContrat;
use App\Models\Contrat\Contrat;
use App\Models\Embauche\Employe;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ContratForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations du contrat')
                    ->schema([
                        Select::make('employe_id')
                            ->label('Salarié')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Employe::query()
                                ->where(function ($query) use ($search): void {
                                    $motif = '%'.mb_strtolower($search).'%';
                                    $query->whereRaw('LOWER(nom) LIKE ?', [$motif])
                                        ->orWhereRaw('LOWER(prenom) LIKE ?', [$motif])
                                        ->orWhereRaw('LOWER(COALESCE(matricule, \'\')) LIKE ?', [$motif]);
                                })
                                ->orderBy('nom')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Employe $employe): array => [
                                    $employe->getKey() => self::libelleEmploye($employe),
                                ])
                                ->all())
                            ->getOptionLabelUsing(function ($value): ?string {
                                $employe = Employe::query()->find($value);

                                return $employe instanceof Employe ? self::libelleEmploye($employe) : null;
                            })
                            ->disabled(fn (?Contrat $record): bool => $record !== null),
                        Select::make('type')
                            ->label('Type de contrat')
                            ->options(collect(TypeContrat::cases())
                                ->mapWithKeys(fn (TypeContrat $type): array => [$type->value => $type->label()])
                                ->all())
                            ->required()
                            ->live()
                            ->disabled(fn (?Contrat $record): bool => $record !== null && ! $record->estModifiable()),
                        DatePicker::make('date_debut')
                            ->label('Date de début')
                            ->required()
                            ->disabled(fn (?Contrat $record): bool => $record !== null && ! $record->estModifiable()),
                        DatePicker::make('date_fin')
                            ->label('Date de fin')
                            ->visible(fn (Get $get): bool => TypeContrat::tryFrom((string) $get('type'))?->aDureeDeterminee() ?? false)
                            ->disabled(fn (?Contrat $record): bool => $record !== null && ! $record->estModifiable()),
                        TextInput::make('salaire')
                            ->label('Salaire')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->suffix('MGA')
                            ->disabled(fn (?Contrat $record): bool => $record !== null && ! $record->estModifiable()),
                        TextInput::make('horaires')
                            ->label('Horaires')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn (?Contrat $record): bool => $record !== null && ! $record->estModifiable()),
                        TextInput::make('lieu_travail')
                            ->label('Lieu de travail')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn (?Contrat $record): bool => $record !== null && ! $record->estModifiable()),
                    ])
                    ->columns(2),
            ]);
    }

    private static function libelleEmploye(Employe $employe): string
    {
        $matricule = $employe->matricule ?? 'Non attribué';

        return "{$employe->prenom} {$employe->nom} ({$matricule})";
    }
}
