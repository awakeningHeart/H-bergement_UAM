<?php

namespace App\Filament\Resources\Chambres\Schemas;

use App\Models\Site;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ChambreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('site_id')
                    ->label('Site')
                    ->options(fn () => Site::query()->accessiblesPar(auth()->user())->orderBy('nom')->pluck('nom', 'id'))
                    ->searchable()
                    ->required()
                    ->live(),
                Select::make('type_chambre_id')->label('Type de chambre')->relationship('type', 'libelle')->searchable()->preload()->required(),
                TextInput::make('numero')
                    ->label('Numéro')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, callable $get) => $rule->where('site_id', $get('site_id'))->whereNull('deleted_at')),
                TextInput::make('etage')->label('Étage')->maxLength(20),
                Textarea::make('description')->label('Description')->columnSpanFull(),
                Toggle::make('actif')->label('Chambre active')->default(true),
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
