<?php

namespace App\Observers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        Cache::forget('form_options.tenants');
    }

    public function updated(Tenant $tenant): void
    {
        if ($tenant->wasChanged(['names', 'mobile_number'])) {
            Cache::forget('form_options.tenants');
        }
    }

    public function deleted(Tenant $tenant): void
    {
        Cache::forget('form_options.tenants');
    }
}
