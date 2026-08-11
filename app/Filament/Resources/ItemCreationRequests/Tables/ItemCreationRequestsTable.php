<?php

namespace App\Filament\Resources\ItemCreationRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemCreationRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('item_name')
                    ->searchable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('inventory_unit')
                    ->searchable(),
                TextColumn::make('purchase_unit')
                    ->searchable(),
                TextColumn::make('sales_unit')
                    ->searchable(),
                TextColumn::make('proposed_item_group')
                    ->searchable(),
                TextColumn::make('item_group')
                    ->searchable(),
                TextColumn::make('item_service_category')
                    ->searchable(),
                IconColumn::make('is_stocked')
                    ->boolean(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('requested_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('classified_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('classified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('creation_triggered_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('creation_triggered_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('synced_to_d365')
                    ->boolean(),
                TextColumn::make('d365_item_id')
                    ->searchable(),
                TextColumn::make('synced_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
