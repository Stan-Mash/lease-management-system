<?php

namespace App\Imports;

use App\Models\Tenant;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TenantsImport implements ToModel, WithChunkReading, WithHeadingRow
{
    public function model(array $row)
    {
        // Skip if name is missing
        if (! isset($row['tenant'])) {
            return null;
        }

        $phone = $row['tenant_number'] ?? '0000000000';
        $idNumber = $row['tenant_id'] ?? null;

        // Treat 0 or empty as null for id_number
        if (empty($idNumber) || $idNumber === '0' || $idNumber === 0) {
            $idNumber = null;
        }

        // 1. Try to find existing tenant by ID Number (if valid)
        if ($idNumber) {
            $existingTenant = Tenant::where('id_number', $idNumber)->first();
            if ($existingTenant) {
                return null; // Already exists, skip.
            }
        }

        // 2. Try to find existing tenant by Phone Number
        $existingTenant = Tenant::where('phone_number', $phone)->first();
        if ($existingTenant) {
            return null; // Already exists, skip.
        }

        // 3. If not found by ID or Phone, create a NEW record
        return new Tenant([
            'full_name' => $row['tenant'],
            'phone_number' => $phone,
            'id_number' => $idNumber,
            'email' => null,
            'notification_preference' => 'SMS',
        ]);
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
