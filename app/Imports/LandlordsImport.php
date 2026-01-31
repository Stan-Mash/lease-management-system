<?php

namespace App\Imports;

use App\Models\Landlord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LandlordsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (! isset($row['lfname'])) {
            return null;
        }

        return new Landlord([
            'landlord_code' => $row['vendorid'] ?? null,
            'name' => $row['lfname'],
            'phone' => $row['tlphon'] ?? '0000000000',
            'email' => null,
        ]);
    }
}
