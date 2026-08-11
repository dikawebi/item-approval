<?php

namespace App\Filament\Resources\ItemCreationRequests\Pages;

use App\Filament\Resources\ItemCreationRequests\ItemCreationRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewItemCreationRequest extends ViewRecord
{
    protected static string $resource = ItemCreationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
