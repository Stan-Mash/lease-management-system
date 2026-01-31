<?php

namespace App\Imports;

use App\Models\Property;
use App\Models\Unit;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UnitsImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    public function model(array $row)
    {
        // Skip if critical data is missing
        if (! isset($row['unitno']) || ! isset($row['blockid'])) {
            return null;
        }

        // 1. Find the Property using the Block ID (e.g. "157C")
        $property = Property::where('property_code', $row['blockid'])->first();

        // If Property doesn't exist yet, we can't create the unit. Skip it.
        if (! $property) {
            return null;
        }

        return new Unit([
            'property_id' => $property->id,
            'unit_number' => $row['unitno'],
            'market_rent' => $row['rntamt'] ?? 0,
            'deposit_required' => 0, // Not in Excel, defaulting to 0
            'type' => 'Standard', // Default type
            'status' => 'VACANT',
        ]);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 100;
    }
}
