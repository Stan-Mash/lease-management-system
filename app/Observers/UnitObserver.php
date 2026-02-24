<?php

namespace App\Observers;

use App\Models\Unit;
use Illuminate\Support\Facades\Cache;

class UnitObserver
{
    public function created(Unit $unit): void
    {
        Cache::forget('form_options.units');
    }

    public function updated(Unit $unit): void
    {
        if ($unit->wasChanged(['unit_number', 'unit_code'])) {
            Cache::forget('form_options.units');
        }
    }

    public function deleted(Unit $unit): void
    {
        Cache::forget('form_options.units');
    }
}
