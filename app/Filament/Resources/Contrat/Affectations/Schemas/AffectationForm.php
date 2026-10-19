<?php

namespace App\Filament\Resources\Contrat\Affectations\Schemas;

use App\Models\Besoin\Departement;
use App\Models\Besoin\Poste;
use App\Models\Embauche\Employe;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AffectationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Affectation')
                    ->schema([
                        Select::make('employe_id')
                            ->label('Salarié')
                            ->options(fn (): array => Employe::query()
                                ->orderBy('nom')
                                ->get()
                                ->mapWithKeys(fn (Employe $employe): array => [
                                    $employe->getKey() => "{$employe->prenom} {$employe->nom} (".($employe->matricule ?? 'Non attribué').')',
                                ])
                                ->all())
                            ->searchable()
                            ->required(),
                        Select::make('departement_id')
                            ->label('Département')
                            ->options(fn (): array => Departement::query()->orderBy('nom')->pluck('nom', 'id')->all())
                            ->searchable()
                            ->live()
                            ->required(),
                        Select::make('poste_id')
                            ->label('Poste')
                            ->options(fn (Get $get): array => $get('departement_id')
                                ? Poste::query()
                                    ->where('departement_id', $get('departement_id'))
                                    ->orderBy('intitule')
                                    ->pluck('intitule', 'id')
                                    ->all()
                                : [])
                            ->searchable()
                            ->disabled(fn (Get $get): bool => blank($get('departement_id')))
                            ->required(),
                        DatePicker::make('date_debut')
                            ->label('Date de début')
                            ->default(now()->toDateString())
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
