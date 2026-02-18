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
        $landlord = Landlord::where('lan_id', $row['llordid'])->first();

        // Safety: If landlord ID is wrong/missing, link to a placeholder so import doesn't fail
        if (! $landlord) {
            $landlord = Landlord::firstOrCreate(
                ['lan_id' => 'UNKNOWN'],
                ['names' => 'Unknown Landlord', 'mobile_number' => '000000'],
            );
        }

        return new Property([
            'property_name' => $row['blockdesc'],
            'reference_number' => $row['blockid'],
            'zone' => $row['zone'] ?? 'A',
            'commission' => 10.0,
            'landlord_id' => $landlord->id,
        ]);
    }
}
