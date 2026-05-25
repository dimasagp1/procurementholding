<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MasterItemTemplateExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return collect([
            [
                'name' => 'Kertas A4 80 GSM',
            ],
            [
                'name' => 'Tinta Printer Epson 003 Black',
            ],
            [
                'name' => 'Pulpen Standard AE7',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
