<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrItemDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'pr_item_id',
        'received_quantity',
        'rejected_quantity',
        'delivery_date',
        'notes',
        'rejection_reason',
        'attachment_path',
        'received_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    public function prItem()
    {
        return $this->belongsTo(PrItem::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
