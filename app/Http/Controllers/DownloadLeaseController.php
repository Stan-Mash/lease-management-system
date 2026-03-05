<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Services\LeasePdfService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DownloadLeaseController extends Controller
{
    private const PDF_CACHE_TTL = 1800;

    public function __construct(
        private readonly LeasePdfService $pdfService,
    ) {}

    /**
     * Download the lease as a PDF attachment.
     */
    public function __invoke(Lease $lease): SymfonyResponse
    {
        $this->authorize('view', $lease);

        return $this->respond($lease, 'attachment');
    }

    /**
     * Preview the lease PDF inline in the browser.
     */
    public function preview(Lease $lease): SymfonyResponse
    {
        $this->authorize('view', $lease);

        return $this->respond($lease, 'inline');
    }

    private function respond(Lease $lease, string $disposition): SymfonyResponse
    {
        // Eager-load everything needed for the cache key and PDF generation.
        $lease->load(['tenant', 'unit', 'property', 'landlord', 'leaseTemplate', 'digitalSignatures']);

        $filename = $this->pdfService->filename($lease);
        $cacheKey = $this->buildCacheKey($lease);

        $binary = Cache::get($cacheKey);

        if ($binary === null) {
            $binary = $this->pdfService->generate($lease);
            Cache::put($cacheKey, $binary, self::PDF_CACHE_TTL);
        }

        return response($binary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Content-Length'      => strlen($binary),
            'Cache-Control'       => 'private, max-age=300',
        ]);
    }

    private function buildCacheKey(Lease $lease): string
    {
        $templateVersion = $lease->leaseTemplate?->version_number ?? 0;
        $leaseUpdated    = $lease->updated_at?->timestamp ?? 0;
        $tenantUpdated   = $lease->tenant?->updated_at?->timestamp ?? 0;

        return sprintf(
            'lease_pdf:%d:v%d:l%d:t%d',
            $lease->id,
            $templateVersion,
            $leaseUpdated,
            $tenantUpdated,
        );
    }
}
