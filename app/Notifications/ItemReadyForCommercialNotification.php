<?php

namespace App\Notifications;

use App\Models\ItemCreationRequest;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class ItemReadyForCommercialNotification
{
    public static function send(User $recipient, ItemCreationRequest $request): void
    {
        Notification::make()
            ->title('Ready to create in D365')
            ->icon('heroicon-o-check-badge')
            ->body(
                "\"{$request->item_name}\" was classified by {$request->classifiedBy->name} — ".
                "group: {$request->item_group}, category: {$request->item_service_category}, ".
                ($request->is_stocked ? 'Stocked' : 'Non-stocked') .
                ". Ready to create in D365."
            )
            ->actions([
                Action::make('create')
                    ->label('Review & Create in D365')
                    ->url(route('filament.item-approval.resources.item-creation-requests.edit', $request))
                    ->button(),
            ])
            ->sendToDatabase($recipient);
    }
}
