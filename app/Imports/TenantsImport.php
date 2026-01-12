<?php

namespace App\Imports;

use App\Models\Tenant;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TenantsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Skip if name is missing
        if (!isset($row['tenant'])) {
            return null;
        }

        $phone = $row['tenant_number'] ?? '0000000000';
        $idNumber = $row['tenant_id'] ?? null;

        // 1. Try to find existing tenant by ID Number (if valid)
        if ($idNumber) {
            $existingTenant = Tenant::where('id_number', $idNumber)->first();
            if ($existingTenant) {
                return $existingTenant; // Found them! Return existing.
            }
        }

        // 2. Try to find existing tenant by Phone Number
        $existingTenant = Tenant::where('phone_number', $phone)->first();
        if ($existingTenant) {
            return $existingTenant; // Found them! Return existing.
        }

        // 3. If not found by ID or Phone, create a NEW record
        return new Tenant([
            'full_name'    => $row['tenant'],
            'phone_number' => $phone,
            'id_number'    => $idNumber,
            'email'        => null,
            'notification_preference' => 'SMS',
        ]);
    }
}
