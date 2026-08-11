<?php

namespace App\Filament\Resources\NumberSequences\Pages;

use App\Filament\Resources\NumberSequences\NumberSequenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNumberSequence extends EditRecord
{
    protected static string $resource = NumberSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
