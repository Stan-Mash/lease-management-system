<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use Barryvdh\DomPDF\Facade\Pdf;

class DownloadLeaseController extends Controller
{
    // __invoke handles the 'download' route
    public function __invoke(Lease $lease)
    {
        return $this->generate($lease, 'download');
    }

    // preview handles the 'preview' route
    public function preview(Lease $lease)
    {
        return $this->generate($lease, 'stream');
    }

    protected function generate(Lease $lease, string $method)
    {
        $lease->load(['tenant', 'unit', 'property', 'landlord']);

        $data = [
            'lease'    => $lease,
            'tenant'   => $lease->tenant,
            'unit'     => $lease->unit,
            'landlord' => $lease->landlord,
            'property' => $lease->property,
            'today'    => now()->format('d/m/Y'),
        ];

        $viewName = match ($lease->lease_type) {
            'residential_major' => 'pdf.residential-major',
            'residential_micro' => 'pdf.residential-micro',
            'commercial'        => 'pdf.commercial',
            default             => 'pdf.residential-major',
        };

        $pdf = Pdf::loadView($viewName, $data);
        $filename = 'Lease-' . $lease->reference_number . '.pdf';

        return $pdf->$method($filename);
    }
}
