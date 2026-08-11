<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ItemWorkflowMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subject,
        public string $body,
        public string $actionUrl,
        public string $actionLabel = 'View Request'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting("Hello " . ($notifiable->name ?? 'User') . ",")
            ->line($this->body)
            ->action($this->actionLabel, $this->actionUrl)
            ->line('Thank you for using our application!');
    }
}
