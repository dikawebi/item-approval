<?php

namespace App\Notifications;

use App\Models\ItemCreationRequest;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class ItemResubmittedNotification
{
    public static function send(User $recipient, ItemCreationRequest $request): void
    {
        Notification::make()
            ->title('Item request revised and resubmitted')
            ->icon('heroicon-o-arrow-path')
            ->body("\"{$request->item_name}\" was revised by {$request->requestedBy->name} after rejection and needs another look.")
            ->actions([
                Action::make('review')
                    ->label('Review & Classify')
                    ->url(route('filament.item-approval.resources.item-creation-requests.edit', $request))
                    ->button(),
            ])
            ->sendToDatabase($recipient);
    }
}
