<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'item_name',
        'description',
        'quantity',
        'uom',
        'estimated_price',
        'total_price',
        'actual_price',
        'actual_total_price',
        'due_date',
        'attachment',
        'status',
        'reject_reason',
        'rejected_by',
        'rejected_at',
        'processed_at',
        'ordered_at',
        'delivered_at',
        'completed_at',
        'revision_count',
        'ready_quantity',
        'po_number',
        'purpose'
    ];

    protected $casts = [
        'estimated_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'actual_price' => 'decimal:2',
        'actual_total_price' => 'decimal:2',
        'rejected_at' => 'datetime',
        'processed_at' => 'datetime',
        'ordered_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    public function canBeApprovedBy($role)
    {
        $approvalFlow = [
            'pending' => ['operational_manager', 'manager_fat'],
            'approved_om' => ['general_manager'],
            'approved_gm' => ['procurement'],
            'approved_proc' => ['procurement'],
            'rejected_om' => ['user'],
            'rejected_gm' => ['user'],
            'rejected_proc' => ['user']
        ];

        return in_array($role, $approvalFlow[$this->status] ?? []);
    }

    public function deliveries()
    {
        return $this->hasMany(PrItemDelivery::class);
    }

    public function deliveryPlans()
    {
        return $this->hasMany(PrItemDeliveryPlan::class, 'pr_item_id');
    }

    public function getReceivedQuantityAttribute()
    {
        return $this->deliveries()->sum('received_quantity');
    }
}