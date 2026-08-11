<?php

namespace App\Notifications;

use App\Models\ItemCreationRequest;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class NewItemCreationRequestNotification
{
    /**
     * Build and send a Filament database (+ optional email) notification
     * to a given user for a new/updated item creation request.
     */
    public static function send(User $recipient, ItemCreationRequest $request): void
    {
        Notification::make()
            ->title('New item creation request')
            ->icon('heroicon-o-cube')
            ->body("\"{$request->item_name}\" was submitted by {$request->requestedBy->name} and is pending accounting classification.")
            ->actions([
                Action::make('review')
                    ->label('Review & Assign')
                    ->url(route('filament.item-approval.resources.item-creation-requests.edit', $request))
                    ->button(),
            ])
            ->sendToDatabase($recipient);

        // Optional: also email accounting. If you want this, create a proper
        // Laravel Notification class (implements ShouldQueue) with a toMail()
        // method, and dispatch it separately here, e.g.:
        // $recipient->notify(new ItemRequestMailNotification($request));
    }
}
