<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrItemDeliveryPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'pr_item_id',
        'planned_date',
        'planned_quantity',
        'notes',
        'attachment_path',
        'is_rescheduled',
        'is_active'
    ];

    protected $casts = [
        'planned_date' => 'date',
        'planned_quantity' => 'decimal:2',
        'is_active' => 'boolean',
        'is_rescheduled' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(PrItem::class, 'pr_item_id');
    }
}
