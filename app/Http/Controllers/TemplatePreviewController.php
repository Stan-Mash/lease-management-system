<?php

namespace App\Http\Controllers;

use App\Models\LeaseTemplate;
use App\Services\SampleLeaseDataService;
use App\Services\TemplateRenderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles template preview with sample data
 */
class TemplatePreviewController extends Controller
{
    protected TemplateRenderService $templateRenderer;

    public function __construct(TemplateRenderService $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
    }

    /**
     * Preview template as PDF with sample data
     */
    public function previewPdf(LeaseTemplate $template)
    {
        try {
            Log::info('Template PDF preview requested', [
                'template_id' => $template->id,
                'template_name' => $template->name,
            ]);

            // Generate sample data
            $sampleData = SampleLeaseDataService::generate($template->template_type);

            // Create a mock lease object from sample data
            $mockLease = $this->createMockLeaseFromSample($sampleData);

            // Render the template with sample data
            $html = $this->templateRenderer->render($template, $mockLease);

            if (empty(trim($html))) {
                throw new Exception('Template rendered empty HTML');
            }

            // Generate PDF
            $pdf = Pdf::loadHTML($html);
            $filename = 'Preview-' . $template->slug . '.pdf';

            Log::info('Template PDF preview generated successfully', [
                'template_id' => $template->id,
            ]);

            // Stream the PDF
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to generate template PDF preview', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->view('errors.template-preview-failed', [
                'error' => $e->getMessage(),
                'template' => $template,
            ], 500);
        }
    }

    /**
     * Preview template as HTML with sample data
     */
    public function previewHtml(LeaseTemplate $template)
    {
        try {
            Log::info('Template HTML preview requested', [
                'template_id' => $template->id,
            ]);

            // Generate sample data
            $sampleData = SampleLeaseDataService::generate($template->template_type);

            // Create a mock lease object from sample data
            $mockLease = $this->createMockLeaseFromSample($sampleData);

            // Render the template
            $html = $this->templateRenderer->render($template, $mockLease);

            if (empty(trim($html))) {
                throw new Exception('Template rendered empty HTML');
            }

            Log::info('Template HTML preview generated successfully', [
                'template_id' => $template->id,
            ]);

            // Return the HTML directly
            return response($html, 200, [
                'Content-Type' => 'text/html',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to generate template HTML preview', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);

            return response()->view('errors.template-preview-failed', [
                'error' => $e->getMessage(),
                'template' => $template,
            ], 500);
        }
    }

    /**
     * Preview template with direct blade rendering (for testing new templates)
     */
    public function previewDirect(Request $request)
    {
        try {
            $templateType = $request->input('type', 'residential_major');
            $viewName = $request->input('view', 'templates.lease-residential-major');

            Log::info('Direct template preview requested', [
                'view' => $viewName,
                'type' => $templateType,
            ]);

            // Generate sample data
            $data = SampleLeaseDataService::generate($templateType);

            // Generate PDF directly from view
            $pdf = Pdf::loadView($viewName, $data);
            $filename = 'Direct-Preview-' . $templateType . '.pdf';

            Log::info('Direct template preview generated successfully');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to generate direct template preview', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to generate preview',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a mock lease object from sample data
     */
    protected function createMockLeaseFromSample(array $sampleData): object
    {
        $mockLease = $sampleData['lease'];
        $mockLease->tenant = $sampleData['tenant'];
        $mockLease->landlord = $sampleData['landlord'];
        $mockLease->property = $sampleData['property'];
        $mockLease->unit = $sampleData['unit'];

        return $mockLease;
    }
}
