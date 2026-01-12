<?php

namespace App\Imports;

use App\Models\Property;
use App\Models\Landlord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PropertiesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (!isset($row['block_description'])) {
            return null;
        }

        // 1. Find the Landlord using the LAN ID
        $landlord = Landlord::where('landlord_code', $row['lan_id'])->first();

        // Safety: If landlord ID is wrong/missing, link to a placeholder so import doesn't fail
        if (!$landlord) {
            $landlord = Landlord::firstOrCreate(
                ['landlord_code' => 'UNKNOWN'],
                ['name' => 'Unknown Landlord', 'phone' => '000000']
            );
        }

        return new Property([
            'name'          => $row['block_description'], // "SUNDAY EXPRESS E"
            'property_code' => $row['block_id'],          // "582A"
            'zone'          => $row['zone'] ?? 'A',
            'management_commission' => $row['commission_rate'] ?? 10.0,
            'landlord_id'   => $landlord->id,
        ]);
    }
}
