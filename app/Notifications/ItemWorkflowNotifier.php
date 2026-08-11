<?php

namespace App\Notifications;

use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Actions\Action as FilamentNotificationAction;

class ItemWorkflowNotifier
{
    /**
     * Send an in-app database notification and queue an email notification to the recipient.
     */
    public static function send(
        User $recipient,
        string $title,
        string $body,
        string $actionUrl,
        string $actionLabel = 'View Request',
        string $icon = 'heroicon-o-information-circle',
        string $color = 'info'
    ): void {
        // 1. Send Filament database notification (in-app drawer)
        FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->color($color)
            ->actions([
                FilamentNotificationAction::make('view')
                    ->label($actionLabel)
                    ->url($actionUrl)
                    ->button(),
            ])
            ->sendToDatabase($recipient);

        // 2. Send Laravel mail notification (queued email)
        if (! empty($recipient->email)) {
            $recipient->notify(new ItemWorkflowMailNotification(
                subject: $title,
                body: $body,
                actionUrl: $actionUrl,
                actionLabel: $actionLabel
            ));
        }
    }
}
