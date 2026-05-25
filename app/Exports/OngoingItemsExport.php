<?php

namespace App\Exports;

use App\Models\PrItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OngoingItemsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'ITEM NAME',
            'SOURCE NAME',
            'DEPARTEMEN',
            'REQUEST DATE',
            'CONFIRMATION DATE',
            'STATUS',
            'QTY INCOMING',
            'OTS',
            'NOTES'
        ];
    }

    public function map($item): array
    {
        $qtyIncoming = (float) $item->received_quantity;
        $ots = (float) $item->quantity - $qtyIncoming;
        $isCleared = $ots <= 0 && $qtyIncoming > 0;
        
        $prNumber = $item->purchaseRequest->pr_number ?? '-';
        $prDept = $item->purchaseRequest->department->code ?? '-';
        
        // Confirmation dates
        $activePlans = $item->deliveryPlans->where('is_active', true);
        $confirmationDates = [];
        foreach ($activePlans as $plan) {
            $dateStr = $plan->planned_date->format('d/m/Y');
            if ($plan->is_rescheduled) {
                $dateStr .= ' [R]';
            }
            $confirmationDates[] = $dateStr;
        }
        $confirmationStr = implode(", ", $confirmationDates) ?: '-';
        
        // Status
        $statusStr = ucfirst(str_replace('_', ' ', $item->status));
        
        // QTY Incoming
        $deliveriesArr = [];
        if ($item->deliveries->isNotEmpty()) {
            foreach($item->deliveries as $delivery) {
                $deliveriesArr[] = (float)$delivery->received_quantity . " " . $item->uom . " (" . $delivery->delivery_date->format('d/m/Y') . ")";
            }
            $qtyStr = implode("\n", $deliveriesArr);
        } else {
            $qtyStr = '-';
        }
        
        // OTS
        $otsStr = $isCleared ? 'Selesai' : "{$ots} {$item->uom}";
        
        // Notes
        $cancelledPlans = $item->deliveryPlans->where('is_active', false);
        $rescheduledPlans = $item->deliveryPlans->where('is_active', true)->where('is_rescheduled', true);
        $displayText = "";
        
        if ($cancelledPlans->isNotEmpty() && $rescheduledPlans->isNotEmpty()) {
            $awal = $cancelledPlans->pluck('planned_date')->map->format('d/m/y')->implode(', ');
            $baru = $rescheduledPlans->pluck('planned_date')->map->format('d/m/y')->implode(', ');
            $displayText = "Reschedule ETA Awal {$awal} - ETA Reschedule {$baru}";
        }
        
        $activeNotes = $item->deliveryPlans->where('is_active', true)->pluck('notes')->filter()->implode(' | ');
        if ($activeNotes) {
            $displayText .= ($displayText ? " | Catatan: " : "Catatan: ") . $activeNotes;
        }
        $notesStr = $displayText ?: '-';

        return [
            $item->item_name,
            $prNumber,
            $prDept,
            $item->purchaseRequest->request_date->format('d M Y'),
            $confirmationStr,
            $statusStr,
            $qtyStr,
            $otsStr,
            $notesStr
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
