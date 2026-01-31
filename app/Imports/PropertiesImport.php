<?php

namespace App\Imports;

use App\Models\Landlord;
use App\Models\Property;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PropertiesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (! isset($row['blockdesc'])) {
            return null;
        }

        // 1. Find the Landlord using the landlord ID from Excel
        $landlord = Landlord::where('landlord_code', $row['llordid'])->first();

        // Safety: If landlord ID is wrong/missing, link to a placeholder so import doesn't fail
        if (! $landlord) {
            $landlord = Landlord::firstOrCreate(
                ['landlord_code' => 'UNKNOWN'],
                ['name' => 'Unknown Landlord', 'phone' => '000000'],
            );
        }

        return new Property([
            'name' => $row['blockdesc'],
            'property_code' => $row['blockid'],
            'zone' => $row['zone'] ?? 'A',
            'management_commission' => 10.0,
            'landlord_id' => $landlord->id,
        ]);
    }
}
