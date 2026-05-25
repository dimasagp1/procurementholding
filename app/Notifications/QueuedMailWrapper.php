<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Generic queued wrapper untuk mengirim email secara async.
 * Gunakan ini bersama notification utama (yang hanya database/sync).
 *
 * Contoh penggunaan di controller:
 *   Notification::send($recipients, new PrStatusUpdatedNotification($pr, $msg)); // instant (database)
 *   Notification::send($recipients, new QueuedMailWrapper(new PrStatusUpdatedNotification($pr, $msg))); // async (mail)
 */
class QueuedMailWrapper extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Notification $notification)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable)
    {
        return $this->notification->toMail($notifiable);
    }
}
