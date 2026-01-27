<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\LeaseTemplate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;

/**
 * Renders Blade templates with lease data
 */
class TemplateRenderServiceV1
{
    /**
     * Render template for a specific lease
     *
     * @param LeaseTemplate $template
     * @param object $lease Can be a Lease model or mock object for preview
     * @return string
     */
    public function render(LeaseTemplate $template, object $lease): string
    {
        // Load relationships if it's a real Lease model
        if ($lease instanceof Lease) {
            $lease->load(['tenant', 'unit', 'property', 'landlord']);
        }

        // Prepare data array
        $data = $this->prepareTemplateData($lease, $template);

        // Create temporary Blade view
        $viewPath = $this->createTemporaryView($template);

        try {
            // Render the view
            $html = View::make($viewPath, $data)->render();

            // Track render metadata
            $this->trackRenderMetadata($lease, $template, $data);

            return $html;
        } finally {
            // Clean up temporary view
            $this->cleanupTemporaryView($viewPath);
        }
    }

    /**
     * Prepare all data available to template
     *
     * @param object $lease
     * @param LeaseTemplate $template
     * @return array
     */
    protected function prepareTemplateData(object $lease, LeaseTemplate $template): array
    {
        $qrCode = $this->getQrCodeDataUri($lease);

        return [
            'lease' => $lease,
            'tenant' => $lease->tenant,
            'unit' => $lease->unit,
            'property' => $lease->property,
            'landlord' => $lease->landlord,
            'today' => now()->format('d/m/Y'),
            'qrCode' => $qrCode, // Match variable name in templates
            'qr_code' => $qrCode, // Also provide snake_case version
            'template' => $template,

            // Helper functions
            'formatMoney' => fn($amount) => 'KES ' . number_format($amount, 2),
            'formatDate' => fn($date, $format = 'd/m/Y') => $date?->format($format),
        ];
    }

    /**
     * Get QR code as base64 data URI
     *
     * @param object $lease
     * @return string|null
     */
    protected function getQrCodeDataUri(object $lease): ?string
    {
        // Only generate real QR codes for actual Lease models
        if (!($lease instanceof Lease)) {
            return null;
        }

        if (class_exists(\App\Services\QRCodeService::class)) {
            try {
                return \App\Services\QRCodeService::getBase64DataUri($lease);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Create temporary Blade view file
     */
    protected function createTemporaryView(LeaseTemplate $template): string
    {
        $viewName = 'pdf.temp_' . $template->id . '_' . time();
        $viewPath = resource_path('views/' . str_replace('.', '/', $viewName) . '.blade.php');

        // Ensure directory exists
        $directory = dirname($viewPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write template content
        file_put_contents($viewPath, $template->blade_content);

        return $viewName;
    }

    /**
     * Clean up temporary view file
     */
    protected function cleanupTemporaryView(string $viewName): void
    {
        $viewPath = resource_path('views/' . str_replace('.', '/', $viewName) . '.blade.php');

        if (file_exists($viewPath)) {
            unlink($viewPath);
        }
    }

    /**
     * Track render metadata
     *
     * @param object $lease
     * @param LeaseTemplate $template
     * @param array $data
     * @return void
     */
    protected function trackRenderMetadata(object $lease, LeaseTemplate $template, array $data): void
    {
        // Only track metadata for actual Lease models, not mock objects
        if (!($lease instanceof Lease)) {
            return;
        }

        // Create or update assignment record
        $lease->templateAssignments()->updateOrCreate(
            ['lease_template_id' => $template->id],
            [
                'template_version_used' => $template->version_number,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'render_metadata' => [
                    'variables_used' => array_keys($data),
                    'rendered_at' => now()->toIso8601String(),
                ],
            ]
        );
    }
}
