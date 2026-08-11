<?php

namespace App\Filament\Resources\D365ItemGroups\Pages;

use App\Filament\Resources\D365ItemGroups\D365ItemGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListD365ItemGroups extends ListRecords
{
    protected static string $resource = D365ItemGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
