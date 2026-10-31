<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i:s', config('uam.timezone'))->sortable(),
                TextColumn::make('log_name')->label('Objet')->badge(),
                TextColumn::make('event')->label('Action')->badge(),
                TextColumn::make('subject_id')->label('N°'),
                TextColumn::make('causer.name')->label('Auteur')->placeholder('Système'),
                TextColumn::make('attribute_changes')->label('Modifications')->limit(80)->wrap()
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : ''),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('log_name')->label('Objet')
                    ->options(fn () => AuditLog::query()->distinct()->pluck('log_name', 'log_name')->all()),
            ]);
    }
}
