<?php

namespace App\Imports;

use App\Models\Landlord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LandlordsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (!isset($row['landlord_name'])) {
            return null;
        }

        return new Landlord([
            // Map the LAN ID from Excel to our new column
            'landlord_code'  => $row['landlord_id'],
            'name'           => $row['landlord_name'],
            'phone'          => $row['telephone'] ?? '0000000000',
            'email'          => $row['email_address'] ?? null,
            'bank_name'      => $row['bank_name'] ?? null,
            'account_number' => $row['account_number'] ?? null,
        ]);
    }
}
