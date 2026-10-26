<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MediaServerInviteComing extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array{heading: string, message: string}
     */
    public function toArray(object $notifiable): array
    {
        $inviter = config('services.plex.inviter_email');
        $admin = config('app.admin_contact_email');

        $fromInviter = $inviter ? ", from {$inviter}" : '';
        $atAddress = $admin ? " at {$admin}" : '';

        return [
            'heading' => 'One more email is on its way',
            'message' => "One more email is coming from Plex{$fromInviter}. Accept it with this same address to see the libraries. If it hasn't arrived within an hour, contact the Admin{$atAddress}.",
        ];
    }
}
