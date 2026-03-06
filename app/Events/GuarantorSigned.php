<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Guarantor;
use App\Models\Lease;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuarantorSigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Guarantor $guarantor,
        public Lease $lease,
    ) {}
}
