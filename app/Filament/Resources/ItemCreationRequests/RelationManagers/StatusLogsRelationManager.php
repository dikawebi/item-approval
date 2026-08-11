<?php

namespace App\Filament\Resources\ItemCreationRequests\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StatusLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'statusLogs';

    protected static ?string $title = 'History';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('to_status')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('from_status')
                    ->label('From')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('to_status')
                    ->label('To')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending', 'creating' => 'warning',
                        'needs_info' => 'info',
                        'classified' => 'primary',
                        'created' => 'success',
                        'rejected', 'create_failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('note')
                    ->label('Details')
                    ->wrap()
                    ->limit(200),

                Tables\Columns\TextColumn::make('requester_response_note')
                    ->label('Requester response')
                    ->wrap()
                    ->limit(200)
                    ->visible(fn ($record) => filled($record?->requester_response_note)),
            ])
            ->defaultSort('created_at', 'desc')
            // Audit trail — no creating, editing, or deleting entries by hand.
            ->headerActions([])
            ->recordActions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
