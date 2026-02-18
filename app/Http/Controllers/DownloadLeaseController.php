<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\LeaseTemplate;
use App\Services\TemplateRenderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DownloadLeaseController extends Controller
{
    /**
     * Cache TTL for generated PDFs (in seconds).
     * PDFs are cached for 30 minutes to avoid regeneration on repeated access.
     */
    private const PDF_CACHE_TTL = 1800;

    public function __construct(
        private readonly TemplateRenderService $templateRenderer,
    ) {}

    /**
     * Download the lease as a PDF file.
     */
    public function __invoke(Lease $lease): SymfonyResponse
    {
        $this->authorize('view', $lease);

        return $this->generate($lease, 'download');
    }

    /**
     * Preview the lease PDF inline in the browser.
     */
    public function preview(Lease $lease): SymfonyResponse
    {
        $this->authorize('view', $lease);

        return $this->generate($lease, 'stream');
    }

    /**
     * Generate PDF using a 3-strategy fallback chain:
     * 1. Assigned custom template
     * 2. Default template for lease type
     * 3. Hardcoded Blade views (backward compatibility)
     *
     * Includes PDF caching, metadata injection, and DRAFT watermark for unsigned leases.
     */
    protected function generate(Lease $lease, string $method): SymfonyResponse
    {
        $lease->load(['tenant', 'unit', 'property', 'landlord', 'leaseTemplate']);
        $filename = 'Lease-' . $lease->reference_number . '.pdf';

        // Check cache first (keyed by lease ID, template version, and last update)
        $cacheKey = $this->buildCacheKey($lease);
        $cachedPdf = Cache::get($cacheKey);

        if ($cachedPdf !== null) {
            Log::debug('PDF served from cache', ['lease_id' => $lease->id, 'cache_key' => $cacheKey]);

            return $this->buildRawPdfResponse($cachedPdf, $filename, $method);
        }

        // Strategy 1: Use assigned custom template
        if ($lease->lease_template_id && $lease->leaseTemplate) {
            try {
                $html = $this->renderTemplate($lease->leaseTemplate, $lease);
                $pdf = $this->createPdf($html, $lease);

                Log::info('PDF generated with custom template', [
                    'lease_id' => $lease->id,
                    'template_id' => $lease->lease_template_id,
                    'template_version' => $lease->template_version_used,
                ]);

                return $this->cacheAndRespond($pdf, $filename, $method, $cacheKey);
            } catch (Exception $e) {
                Log::error('Failed to generate PDF with custom template', [
                    'lease_id' => $lease->id,
                    'template_id' => $lease->lease_template_id,
                    'error' => $e->getMessage(),
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
                $html = $this->renderTemplate($defaultTemplate, $lease);
                $pdf = $this->createPdf($html, $lease);

                Log::info('PDF generated with default template', [
                    'lease_id' => $lease->id,
                    'template_id' => $defaultTemplate->id,
                    'template_type' => $defaultTemplate->template_type,
                ]);

                return $this->cacheAndRespond($pdf, $filename, $method, $cacheKey);
            } catch (Exception $e) {
                Log::error('Failed to generate PDF with default template', [
                    'lease_id' => $lease->id,
                    'template_id' => $defaultTemplate->id,
                    'error' => $e->getMessage(),
                ]);

                if (config('app.debug')) {
                    throw $e;
                }
                // Fall through to strategy 3
            }
        }

        // Strategy 3: Fallback to hardcoded Blade views
        return $this->generateWithHardcodedViews($lease, $filename, $method, $cacheKey);
    }

    /**
     * Generate PDF using hardcoded Blade views (backward compatibility fallback).
     */
    protected function generateWithHardcodedViews(
        Lease $lease,
        string $filename,
        string $method,
        string $cacheKey,
    ): SymfonyResponse {
        $data = [
            'lease' => $lease,
            'tenant' => $lease->tenant,
            'unit' => $lease->unit,
            'landlord' => $lease->landlord,
            'property' => $lease->property,
            'today' => now()->format('d/m/Y'),
        ];

        $viewName = match ($lease->lease_type) {
            'residential_major' => 'pdf.residential-major',
            'residential_micro' => 'pdf.residential-micro',
            'commercial' => 'pdf.commercial',
            default => 'pdf.residential-major',
        };

        try {
            $pdf = Pdf::loadView($viewName, $data);

            // Add metadata and DRAFT watermark for hardcoded views too
            $this->setPdfMetadata($pdf, $lease);

            Log::info('PDF generated with hardcoded views (fallback)', [
                'lease_id' => $lease->id,
                'view_name' => $viewName,
            ]);

            return $this->cacheAndRespond($pdf, $filename, $method, $cacheKey);
        } catch (Exception $e) {
            Log::error('Failed to generate PDF with hardcoded views', [
                'lease_id' => $lease->id,
                'view_name' => $viewName,
                'error' => $e->getMessage(),
            ]);

            if ($method === 'stream') {
                return response()->view('errors.pdf-generation-failed', [
                    'error' => $e->getMessage(),
                    'lease' => $lease,
                ], 500);
            }

            abort(500, 'Failed to generate PDF. Please try again later.');
        }
    }

    /**
     * Create a PDF from HTML, applying metadata and DRAFT watermark as needed.
     */
    private function createPdf(string $html, Lease $lease): DomPDF
    {
        // Inject DRAFT watermark for unsigned leases
        if ($this->needsDraftWatermark($lease)) {
            $html = $this->injectDraftWatermark($html);
        }

        $pdf = Pdf::loadHTML($html);

        // Inject PDF metadata for better document tracking
        $this->setPdfMetadata($pdf, $lease);

        return $pdf;
    }

    /**
     * Render a template and validate the output is not empty.
     *
     * @throws Exception If the rendered HTML is empty
     */
    private function renderTemplate(LeaseTemplate $template, Lease $lease): string
    {
        $html = $this->templateRenderer->render($template, $lease);

        if (empty(trim($html))) {
            throw new Exception('Template rendered empty HTML');
        }

        return $html;
    }

    /**
     * Build a unique cache key based on lease data and template version.
     * Cache is automatically invalidated when the lease or template is updated.
     */
    private function buildCacheKey(Lease $lease): string
    {
        $templateVersion = $lease->leaseTemplate?->version_number ?? 0;
        $leaseUpdated = $lease->updated_at?->timestamp ?? 0;
        $tenantUpdated = $lease->tenant?->updated_at?->timestamp ?? 0;

        return sprintf(
            'lease_pdf:%d:v%d:l%d:t%d',
            $lease->id,
            $templateVersion,
            $leaseUpdated,
            $tenantUpdated,
        );
    }

    /**
     * Cache the PDF output and return the response.
     */
    private function cacheAndRespond(DomPDF $pdf, string $filename, string $method, string $cacheKey): SymfonyResponse
    {
        $output = $pdf->output();

        // Cache the raw PDF binary
        Cache::put($cacheKey, $output, self::PDF_CACHE_TTL);

        return $this->buildRawPdfResponse($output, $filename, $method);
    }

    /**
     * Build response from raw PDF binary data (used for cached PDFs).
     */
    private function buildRawPdfResponse(string $pdfContent, string $filename, string $method): SymfonyResponse
    {
        $disposition = $method === 'stream' ? 'inline' : 'attachment';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Content-Length' => strlen($pdfContent),
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * Set PDF metadata for document identification and tracking.
     */
    private function setPdfMetadata(DomPDF $pdf, Lease $lease): void
    {
        $dompdf = $pdf->getDomPDF();

        $dompdf->addInfo('Title', 'Lease Agreement - ' . ($lease->reference_number ?? 'Draft'));
        $dompdf->addInfo('Author', 'Chabrin Agencies Ltd');
        $dompdf->addInfo('Creator', 'Chabrin Lease Management System');
        $dompdf->addInfo('Producer', 'Chabrin Agencies Ltd - DomPDF');
        $dompdf->addInfo('Subject', sprintf(
            '%s Lease - %s - %s',
            ucfirst(str_replace('_', ' ', $lease->lease_type ?? 'General')),
            $lease->tenant?->full_name ?? 'Unknown Tenant',
            $lease->property?->name ?? 'Unknown Property',
        ));
        $dompdf->addInfo('Keywords', implode(', ', [
            'lease',
            'agreement',
            $lease->reference_number ?? '',
            $lease->lease_type ?? '',
            $lease->property?->name ?? '',
        ]));
        $dompdf->addInfo('CreationDate', now()->format('D:YmdHis'));
    }

    /**
     * Determine if the lease needs a DRAFT watermark.
     * Unsigned/unapproved leases get a watermark to prevent premature use.
     */
    private function needsDraftWatermark(Lease $lease): bool
    {
        $workflowState = $lease->workflow_state;

        // If workflow_state is a string, convert to enum
        if (is_string($workflowState)) {
            $workflowState = LeaseWorkflowState::tryFrom($workflowState);
        }

        if (! $workflowState instanceof LeaseWorkflowState) {
            return true; // Default to DRAFT watermark if state is unknown
        }

        // States that indicate the lease is NOT yet fully executed
        $draftStates = [
            LeaseWorkflowState::DRAFT,
            LeaseWorkflowState::RECEIVED,
            LeaseWorkflowState::PENDING_LANDLORD_APPROVAL,
            LeaseWorkflowState::APPROVED,
            LeaseWorkflowState::PRINTED,
            LeaseWorkflowState::CHECKED_OUT,
            LeaseWorkflowState::SENT_DIGITAL,
            LeaseWorkflowState::PENDING_OTP,
            LeaseWorkflowState::PENDING_TENANT_SIGNATURE,
            LeaseWorkflowState::RETURNED_UNSIGNED,
            LeaseWorkflowState::DISPUTED,
        ];

        return in_array($workflowState, $draftStates, true);
    }

    /**
     * Inject a DRAFT watermark into the HTML before PDF rendering.
     * Uses CSS to overlay a diagonal "DRAFT" text across each page.
     */
    private function injectDraftWatermark(string $html): string
    {
        $watermarkCss = '
            <style>
                .draft-watermark {
                    position: fixed;
                    top: 35%;
                    left: 10%;
                    width: 80%;
                    text-align: center;
                    z-index: -1;
                    opacity: 0.06;
                    font-size: 120pt;
                    font-weight: bold;
                    color: #cc0000;
                    transform: rotate(-35deg);
                    -webkit-transform: rotate(-35deg);
                    letter-spacing: 20px;
                    pointer-events: none;
                }
            </style>
        ';

        $watermarkHtml = '<div class="draft-watermark">DRAFT</div>';

        // Inject CSS before </head> and watermark after <body>
        if (stripos($html, '</head>') !== false) {
            $html = str_ireplace('</head>', $watermarkCss . '</head>', $html);
        } else {
            // If no <head> tag, prepend the CSS
            $html = $watermarkCss . $html;
        }

        if (stripos($html, '<body>') !== false) {
            $html = str_ireplace('<body>', '<body>' . $watermarkHtml, $html);
        } elseif (stripos($html, '<body') !== false) {
            // Handle <body class="..."> or <body style="...">
            $html = preg_replace('/<body([^>]*)>/i', '<body$1>' . $watermarkHtml, $html);
        }

        return $html;
    }
}
