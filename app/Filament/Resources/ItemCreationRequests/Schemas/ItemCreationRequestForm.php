<?php

namespace App\Filament\Resources\ItemCreationRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ItemCreationRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('item_name')
                    ->required(),
                TextInput::make('description'),
                TextInput::make('inventory_unit'),
                TextInput::make('purchase_unit'),
                TextInput::make('sales_unit'),
                TextInput::make('proposed_item_group'),
                TextInput::make('item_group'),
                TextInput::make('item_service_category'),
                Toggle::make('is_stocked'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('requested_by')
                    ->required()
                    ->numeric(),
                TextInput::make('classified_by')
                    ->numeric(),
                DateTimePicker::make('classified_at'),
                Textarea::make('rejection_reason')
                    ->columnSpanFull(),
                Textarea::make('info_request_note')
                    ->columnSpanFull(),
                TextInput::make('creation_triggered_by')
                    ->numeric(),
                DateTimePicker::make('creation_triggered_at'),
                Toggle::make('synced_to_d365')
                    ->required(),
                TextInput::make('d365_item_id'),
                Textarea::make('sync_error')
                    ->columnSpanFull(),
                DateTimePicker::make('synced_at'),
            ]);
    }
}
