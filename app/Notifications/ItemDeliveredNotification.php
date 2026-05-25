<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\PrItem;


class ItemDeliveredNotification extends Notification
{
    use Queueable;

    public $item;

    public function __construct(PrItem $item)
    {
        $this->item = $item;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'item_id' => $this->item->id,
            'item_name' => $this->item->item_name,
            'pr_number' => $this->item->purchaseRequest->pr_number,
            'message' => "Item '{$this->item->item_name}' telah dikirim (Delivered).",
            'type' => 'status_update',
            'url' => route('purchase-requests.show', $this->item->purchase_request_id),
        ];
    }

    public function toMail(object $notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
                    ->subject('Barang Diterima: ' . $this->item->purchaseRequest->pr_number)
                    ->greeting('Halo,')
                    ->line("Item '{$this->item->item_name}' telah dikirim (Delivered).")
                    ->action('Lihat Detail PR', route('purchase-requests.show', $this->item->purchase_request_id))
                    ->line('Terima kasih telah menggunakan PR System.');
    }
}
