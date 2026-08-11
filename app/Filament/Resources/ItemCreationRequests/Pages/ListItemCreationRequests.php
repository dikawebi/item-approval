<?php

namespace App\Filament\Resources\ItemCreationRequests\Pages;

use App\Filament\Resources\ItemCreationRequests\ItemCreationRequestResource;
use App\Models\ItemCreationRequest;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListItemCreationRequests extends ListRecords
{
    protected static string $resource = ItemCreationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Poll every 3 seconds while any request is being processed by the queue.
     * Returns null (no polling) once all jobs have resolved, avoiding
     * unnecessary network traffic during normal usage.
     */
    public function getPollingInterval(): ?string
    {
        return ItemCreationRequest::where('status', 'creating')->exists()
            ? '3s'
            : null;
    }
}
