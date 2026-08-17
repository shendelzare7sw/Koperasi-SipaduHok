<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IdentityVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly string $destination,
        private readonly ?int $buyerId = null,
        private readonly string $icon = 'fa-id-card',
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string|null> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'destination' => $this->destination,
            'buyer_id' => $this->buyerId,
            'icon' => $this->icon,
        ];
    }
}
