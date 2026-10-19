<?php

namespace App\Filament\Resources\Contrat\Documents\Schemas;

use App\Enums\TypeDocument;
use App\Models\Contrat\Contrat;
use App\Models\Embauche\Employe;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document')
                    ->schema([
                        Select::make('cible_type')
                            ->label('Nature de la cible')
                            ->options([
                                'contrat' => 'Contrat',
                                'employe' => 'Salarié',
                            ])
                            ->live()
                            ->required(),
                        Select::make('cible_id')
                            ->label('Cible')
                            ->options(fn (Get $get): array => match ($get('cible_type')) {
                                'contrat' => Contrat::query()
                                    ->with('employe')
                                    ->get()
                                    ->mapWithKeys(fn (Contrat $contrat): array => [
                                        $contrat->getKey() => 'Contrat '.$contrat->getKey().' — '
                                            .$contrat->employe->prenom.' '.$contrat->employe->nom,
                                    ])
                                    ->all(),
                                'employe' => Employe::query()
                                    ->orderBy('nom')
                                    ->get()
                                    ->mapWithKeys(fn (Employe $employe): array => [
                                        $employe->getKey() => "{$employe->prenom} {$employe->nom} ("
                                            .($employe->matricule ?? 'Non attribué').')',
                                    ])
                                    ->all(),
                                default => [],
                            })
                            ->searchable()
                            ->required()
                            ->disabled(fn (Get $get): bool => blank($get('cible_type'))),
                        Select::make('type')
                            ->label('Type de document')
                            ->options(collect(TypeDocument::cases())
                                ->mapWithKeys(fn (TypeDocument $type): array => [$type->value => $type->label()])
                                ->all())
                            ->required(),
                        TextInput::make('nom')
                            ->label('Nom')
                            ->maxLength(255),
                        FileUpload::make('fichier')
                            ->label('Fichier')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(10240)
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
