<?php

namespace App\Exports;

use App\Models\PurchaseRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseRequestExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;
    protected $startDate;
    protected $endDate;

    public function __construct($status = null, $startDate = null, $endDate = null)
    {
        $this->status = $status;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = PurchaseRequest::with(['user', 'department', 'items.approvals', 'items.rejectedBy']);

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        $prs = $query->get();

        if ($this->status) {
            $prs = $prs->filter(function($pr) {
                return strtolower($pr->approval_status) === strtolower($this->status) ||
                       ($this->status === 'approved_om' && $pr->approval_status === 'Approved (OM)') ||
                       ($this->status === 'approved_gm' && $pr->approval_status === 'Approved (GM)') ||
                       ($this->status === 'approved_proc' && $pr->approval_status === 'Approved (Proc)') ||
                       ($this->status === 'rejected' && $pr->approval_status === 'Revision Required');
            });
        }

        $items = collect();
        foreach ($prs as $pr) {
            foreach ($pr->items as $item) {
                $item->pr_ref = $pr; // Keep reference to PR
                $items->push($item);
            }
        }

        return $items;
    }

    public function headings(): array
    {
        return [
            'PR Number',
            'Department',
            'Requester',
            'PR Date',
            'Purpose',
            'Item Name',
            'Description',
            'Qty',
            'UOM',
            'Item Status',
            'Due Date / Keterangan',
            'Level 1 Approved At',
            'GM Approved At',
            'Proc Approved At',
            'Processed At',
            'Ordered At',
            'Delivered At',
            'Completed At',
            'Rejected At',
            'Reject Reason',
            'Rejected By',
        ];
    }

    public function map($item): array
    {
        $pr = $item->pr_ref;
        
        $omApproval = $item->approvals->whereIn('approver_role', ['operational_manager', 'manager_fat'])->first();
        $gmApproval = $item->approvals->where('approver_role', 'general_manager')->first();
        $procApproval = $item->approvals->where('approver_role', 'procurement')->where('status', 'approved')->first();

        return [
            $pr->pr_number,
            $pr->department->name,
            $pr->user->name,
            $pr->request_date?->format('Y-m-d') ?? 'N/A',
            $pr->purpose,
            $item->item_name,
            $item->description,
            $item->quantity,
            $item->uom,
            ucfirst(str_replace('_', ' ', $item->status)),
            $item->due_date ?? '-',
            $omApproval?->approved_at?->format('Y-m-d H:i:s') ?? '-',
            $gmApproval?->approved_at?->format('Y-m-d H:i:s') ?? '-',
            $procApproval?->approved_at?->format('Y-m-d H:i:s') ?? '-',
            $item->processed_at?->format('Y-m-d H:i:s') ?? '-',
            $item->ordered_at?->format('Y-m-d H:i:s') ?? '-',
            $item->delivered_at?->format('Y-m-d H:i:s') ?? '-',
            $item->completed_at?->format('Y-m-d H:i:s') ?? '-',
            $item->rejected_at?->format('Y-m-d H:i:s') ?? '-',
            $item->reject_reason ?? '-',
            $item->rejectedBy?->name ?? '-',
        ];
    }
}
