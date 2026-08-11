<?php

namespace App\Filament\Resources\ItemCreationRequests\Pages;

use App\Filament\Resources\ItemCreationRequests\ItemCreationRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
class CreateItemCreationRequest extends CreateRecord
{
    protected static string $resource = ItemCreationRequestResource::class;

   protected function mutateFormDataBeforeCreate(array $data): array
{
    /** @var \App\Models\User $user */
    $user = Auth::User();

    $data['requested_by'] = $user->id;
    $data['status'] = 'pending';

    return $data;
}
}