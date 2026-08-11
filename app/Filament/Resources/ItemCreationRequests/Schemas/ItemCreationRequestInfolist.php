<?php

namespace App\Filament\Resources\ItemCreationRequests\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ItemCreationRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('item_name'),
                TextEntry::make('description')
                    ->placeholder('-'),
                TextEntry::make('inventory_unit')
                    ->placeholder('-'),
                TextEntry::make('purchase_unit')
                    ->placeholder('-'),
                TextEntry::make('sales_unit')
                    ->placeholder('-'),
                TextEntry::make('proposed_item_group')
                    ->placeholder('-'),
                TextEntry::make('item_group')
                    ->placeholder('-'),
                TextEntry::make('item_service_category')
                    ->placeholder('-'),
                IconEntry::make('is_stocked')
                    ->boolean()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('requested_by')
                    ->numeric(),
                TextEntry::make('classified_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('classified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('rejection_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('info_request_note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('creation_triggered_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('creation_triggered_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('synced_to_d365')
                    ->boolean(),
                TextEntry::make('assigned_item_number')
                    ->label('Assigned Item No.')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Item number copied'),
                TextEntry::make('d365_item_id')
                    ->placeholder('-'),
                TextEntry::make('sync_error')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('synced_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
