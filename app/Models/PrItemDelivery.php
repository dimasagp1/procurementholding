<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrItemDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'pr_item_id',
        'retur_for_delivery_id',
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

    public function returForDelivery()
    {
        return $this->belongsTo(PrItemDelivery::class, 'retur_for_delivery_id');
    }

    public function returReceipts()
    {
        return $this->hasMany(PrItemDelivery::class, 'retur_for_delivery_id');
    }

    public function isReturReceipt()
    {
        return !is_null($this->retur_for_delivery_id);
    }

    public function getUnresolvedRejectedQuantityAttribute()
    {
        if ($this->rejected_quantity <= 0) {
            return 0;
        }
        $receivedReplacement = $this->returReceipts()->sum('received_quantity');
        return max(0, (float) $this->rejected_quantity - (float) $receivedReplacement);
    }
}
