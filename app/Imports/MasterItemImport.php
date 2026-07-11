<?php

namespace App\Imports;

use App\Models\MasterItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MasterItemImport implements ToModel, WithHeadingRow
{
    protected $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if (!isset($row['name']) || empty(trim($row['name']))) {
            return null;
        }

        // Avoid duplicates for this company
        $exists = MasterItem::where('name', $row['name'])
            ->where('company_id', $this->companyId)
            ->exists();
        if ($exists) {
            return null;
        }

        return new MasterItem([
            'name' => $row['name'],
            'company_id' => $this->companyId,
        ]);
    }
}
