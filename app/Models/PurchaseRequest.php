<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use App\Traits\GeneratesPrNumber;

class PurchaseRequest extends Model
{
    use HasFactory, GeneratesPrNumber;

    protected $fillable = [
        'pr_number',
        'user_id',
        'department_id',
        'company_id',
        'request_date',
        'purpose',
        'status',
        'pr_type',
        'total_amount',
        'notes'
    ];

    protected $casts = [
        'request_date' => 'date',
        'total_amount' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function items()
    {
        return $this->hasMany(PrItem::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    public function getApprovalStatusAttribute()
    {
        if ($this->status === 'draft') return 'Draft';
        
        $items = $this->items;
        if ($items->isEmpty()) return 'Pending';

        // Check if any item is pending estimate
        if ($items->contains('status', 'pending_estimate')) {
            return 'Pending Estimate';
        }

        if ($this->hasRejectedItems()) {
            // Check if any item is already approved or further
            $hasProgress = $items->contains(function($item) {
                return !in_array($item->status, ['pending', 'pending_estimate', 'rejected_om', 'rejected_gm', 'rejected_proc', 'rejected_holding']);
            });
            return $hasProgress ? 'Partial / Revision' : 'Revision Required';
        }

        $statuses = $items->pluck('status')->toArray();

        // Check if all items are at least at a certain stage
        if ($this->allAtLeast($statuses, ['completed'])) return 'Completed';
        if ($this->allAtLeast($statuses, ['completed', 'delivered'])) return 'Delivered';
        if ($this->allAtLeast($statuses, ['completed', 'delivered', 'ordered'])) return 'Ordered';
        if ($this->allAtLeast($statuses, ['completed', 'delivered', 'ordered', 'approved_proc'])) return 'Menunggu Procurement Holding';
        if ($this->allAtLeast($statuses, ['completed', 'delivered', 'ordered', 'approved_proc', 'approved_gm'])) return 'Approved (GM)';
        $level1Label = $this->pr_type === 'non_operational' ? 'Approved (FAT)' : 'Approved (OM)';
        if ($this->allAtLeast($statuses, ['completed', 'delivered', 'ordered', 'approved_proc', 'approved_gm', 'approved_om'])) return $level1Label;

        // If not all at least OM but some are OM, it's Processing
        if (collect($statuses)->contains(fn($s) => !in_array($s, ['pending', 'pending_estimate']))) {
            return 'Processing';
        }

        return 'Pending';
    }

    /**
     * Helper to check if all items have reached at least a certain stage.
     */
    private function allAtLeast($currentStatuses, $targetStatuses)
    {
        foreach ($currentStatuses as $status) {
            if (!in_array($status, $targetStatuses)) return false;
        }
        return true;
    }

    /**
     * Check if PR has any rejected items.
     */
    public function hasRejectedItems()
    {
        return $this->items()
            ->whereIn('status', ['rejected_om', 'rejected_gm', 'rejected_proc', 'rejected_holding'])
            ->exists();
    }

    /**
     * Check if the PR can be edited by the user.
     */
    public function isEditable()
    {
        // Draft is always editable
        if ($this->status === 'draft') return true;

        // If it has rejected items, it's in revision mode
        if ($this->hasRejectedItems()) return true;

        // If it's pending (or any of its items are pending_estimate/pending and not approved yet)
        if ($this->status === 'pending') {
            $hasApprovals = $this->items()->whereIn('status', [
                'approved_om', 'approved_gm', 'approved_proc', 'ordered', 'delivered', 'completed'
            ])->exists();
            return !$hasApprovals;
        }

        return false;
    }

    /**
     * Check if the PR can be deleted by the user.
     */
    public function isDeletable()
    {
        // Draft is always deletable
        if ($this->status === 'draft') return true;

        // Pending is deletable ONLY if it has NO approvals started
        if ($this->status === 'pending') {
            if ($this->items()->count() === 0) return true;

            $hasApprovals = $this->items()->whereIn('status', [
                'approved_om', 'approved_gm', 'approved_proc', 'ordered', 'delivered', 'completed'
            ])->exists();
            return !$hasApprovals;
        }

        return false;
    }

    public function getEligibleItemsForUser($user, $action)
    {
        if (!$user) {
            return collect();
        }
        $isOm = $user->hasRole('operational_manager');
        $isFat = $user->hasRole('manager_fat');
        $isGm = $user->hasRole('general_manager');
        $isProc = $user->hasRole('procurement');
        $isProcHolding = $user->hasRole('procurement_holding');
        $isSuperadmin = $user->hasRole('superadmin');
        
        $role = $user->getRoleNames()->first();

        return $this->items->filter(function ($item) use ($user, $isOm, $isFat, $isGm, $isProc, $isProcHolding, $isSuperadmin, $role, $action) {
            if ($this->status === 'draft') {
                return false;
            }

            if ($action === 'approve' || $action === 'reject') {
                $isLevel1Approver = ($this->pr_type === 'non_operational') ? $isFat : $isOm;
                
                if (($isLevel1Approver || $isSuperadmin) && $item->status === 'pending') {
                    return true;
                }
                if (($isGm || $isSuperadmin) && $item->status === 'approved_om') {
                    return true;
                }
                if (($isProc || $isSuperadmin) && $item->status === 'approved_gm') {
                    return true;
                }
                if (($isProcHolding || $isSuperadmin) && $item->status === 'approved_proc') {
                    return true;
                }
            } elseif ($action === 'note') {
                if ($this->user_id == $user->id) {
                    return true;
                }
                if (($isProc || $isSuperadmin) && $item->status === 'pending_estimate') {
                    return true;
                }
                if ($isSuperadmin && in_array($item->status, ['pending', 'approved_om', 'approved_gm', 'approved_proc'])) {
                    return true;
                }
                if ($isOm && $item->status === 'pending' && $this->pr_type === 'operational') {
                    return true;
                }
                if ($isFat && $item->status === 'pending' && $this->pr_type === 'non_operational') {
                    return true;
                }
                if ($isGm && $item->status === 'approved_om') {
                    return true;
                }
                if ($isProc && $item->status === 'approved_gm') {
                    return true;
                }
                if ($isProcHolding && $item->status === 'approved_proc') {
                    return true;
                }
            }

            return false;
        });
    }
}