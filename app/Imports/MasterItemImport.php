<?php

namespace App\Imports;

use App\Models\MasterItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MasterItemImport implements ToModel, WithHeadingRow
{
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

        // Avoid duplicates
        $exists = MasterItem::where('name', $row['name'])->exists();
        if ($exists) {
            return null;
        }

        return new MasterItem([
            'name' => $row['name'],
        ]);
    }
}
