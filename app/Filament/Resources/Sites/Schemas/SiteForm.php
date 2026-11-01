<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ville_id')->label('Ville')->relationship('ville', 'nom')->searchable()->preload()->required(),
                TextInput::make('nom')
                    ->label('Nom du site')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label('Identifiant (URL)')->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('adresse')->label('Adresse')->maxLength(255),
                TextInput::make('telephone')->label('Téléphone')->tel()->maxLength(20),
                TextInput::make('latitude')->label('Latitude')->numeric()->minValue(-90)->maxValue(90),
                TextInput::make('longitude')->label('Longitude')->numeric()->minValue(-180)->maxValue(180),
                Textarea::make('description')->label('Description')->columnSpanFull(),
                Select::make('personnel')
                    ->label('Personnel affecté (gestionnaires)')
                    ->relationship('personnel', 'name')
                    ->multiple()
                    ->preload()
                    ->columnSpanFull(),
                Toggle::make('actif')->label('Site actif')->default(true),
                Repeater::make('photos')
                    ->label('Photos')
                    ->relationship()
                    ->schema([
                        FileUpload::make('chemin')
                            ->label('Image')
                            ->image()
                            // Pas de SVG : un SVG servi depuis notre domaine peut contenir du script.
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('photos')
                            ->maxSize(4096)
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1600')
                            ->imageResizeTargetHeight('1200')
                            ->required(),
                    ])
                    ->orderColumn('ordre')
                    ->reorderable()
                    ->addActionLabel('Ajouter une photo')
                    ->defaultItems(0)
                    ->grid(3)
                    ->columnSpanFull(),
            ]);
    }
}
