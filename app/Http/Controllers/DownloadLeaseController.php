<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\LeaseTemplate;
use App\Services\TemplateRenderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class DownloadLeaseController extends Controller
{
    protected TemplateRenderService $templateRenderer;

    public function __construct(TemplateRenderService $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
    }

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
        $lease->load(['tenant', 'unit', 'property', 'landlord', 'leaseTemplate']);

        // Strategy 1: Use assigned custom template
        if ($lease->lease_template_id && $lease->leaseTemplate) {
            try {
                Log::info('Attempting PDF generation with custom template', [
                    'lease_id' => $lease->id,
                    'template_id' => $lease->lease_template_id,
                    'method' => $method,
                ]);

                $html = $this->templateRenderer->render($lease->leaseTemplate, $lease);

                // Ensure HTML is not empty
                if (empty(trim($html))) {
                    throw new \Exception('Template rendered empty HTML');
                }

                $pdf = Pdf::loadHTML($html);
                $filename = 'Lease-' . $lease->reference_number . '.pdf';

                Log::info('Successfully generated PDF using custom template', [
                    'lease_id' => $lease->id,
                    'template_id' => $lease->lease_template_id,
                    'template_version' => $lease->template_version_used,
                ]);

                // Call the method directly to ensure proper PDF response
                if ($method === 'stream') {
                    return response($pdf->output(), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="' . $filename . '"',
                    ]);
                } else {
                    return $pdf->download($filename);
                }
            } catch (\Exception $e) {
                Log::error('Failed to generate PDF with custom template', [
                    'lease_id' => $lease->id,
                    'template_id' => $lease->lease_template_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Fall through to strategy 2
            }
        }

        // Strategy 2: Use default template for this lease type
        $defaultTemplate = LeaseTemplate::where('template_type', $lease->lease_type)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if ($defaultTemplate) {
            try {
                Log::info('Attempting PDF generation with default template', [
                    'lease_id' => $lease->id,
                    'template_id' => $defaultTemplate->id,
                    'method' => $method,
                ]);

                $html = $this->templateRenderer->render($defaultTemplate, $lease);

                // Ensure HTML is not empty
                if (empty(trim($html))) {
                    throw new \Exception('Template rendered empty HTML');
                }

                $pdf = Pdf::loadHTML($html);
                $filename = 'Lease-' . $lease->reference_number . '.pdf';

                Log::info('Successfully generated PDF using default template', [
                    'lease_id' => $lease->id,
                    'template_id' => $defaultTemplate->id,
                    'template_type' => $defaultTemplate->template_type,
                ]);

                // Call the method directly to ensure proper PDF response
                if ($method === 'stream') {
                    return response($pdf->output(), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="' . $filename . '"',
                    ]);
                } else {
                    return $pdf->download($filename);
                }
            } catch (\Exception $e) {
                Log::error('Failed to generate PDF with default template', [
                    'lease_id' => $lease->id,
                    'template_id' => $defaultTemplate->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // In development, show the error
                if (config('app.debug')) {
                    throw $e;
                }

                // Fall through to strategy 3
            }
        }

        // Strategy 3: Fallback to hardcoded views (backward compatibility)
        Log::info('Falling back to hardcoded views', [
            'lease_id' => $lease->id,
            'lease_type' => $lease->lease_type,
        ]);

        return $this->generateWithHardcodedViews($lease, $method);
    }

    protected function generateWithHardcodedViews(Lease $lease, string $method)
    {
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

        try {
            $pdf = Pdf::loadView($viewName, $data);
            $filename = 'Lease-' . $lease->reference_number . '.pdf';

            Log::info('Successfully generated PDF using hardcoded views (fallback)', [
                'lease_id' => $lease->id,
                'view_name' => $viewName,
            ]);

            // Call the method directly to ensure proper PDF response
            if ($method === 'stream') {
                return response($pdf->output(), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                ]);
            } else {
                return $pdf->download($filename);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate PDF with hardcoded views', [
                'lease_id' => $lease->id,
                'view_name' => $viewName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return error response
            if ($method === 'stream') {
                return response()->view('errors.pdf-generation-failed', [
                    'error' => $e->getMessage(),
                    'lease' => $lease,
                ], 500);
            } else {
                abort(500, 'Failed to generate PDF: ' . $e->getMessage());
            }
        }
    }
}
