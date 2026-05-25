<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\PurchaseRequest;


class PrActionRequiredNotification extends Notification
{
    use Queueable;

    public $purchaseRequest;
    public $message;

    public function __construct(PurchaseRequest $purchaseRequest, string $message)
    {
        $this->purchaseRequest = $purchaseRequest;
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'pr_id' => $this->purchaseRequest->id,
            'pr_number' => $this->purchaseRequest->pr_number,
            'message' => $this->message,
            'type' => 'action_required',
            'url' => route('purchase-requests.show', $this->purchaseRequest->id),
        ];
    }

    public function toMail(object $notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
                    ->subject('Butuh Tindakan: PR ' . $this->purchaseRequest->pr_number)
                    ->greeting('Tindakan Diperlukan,')
                    ->line($this->message)
                    ->action('Tinjau PR Sekarang', route('purchase-requests.show', $this->purchaseRequest->id))
                    ->line('Harap segera memproses permintaan ini.');
    }
}
