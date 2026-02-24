<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\LeaseTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\Log;

class LeasePdfService
{
    public function __construct(
        private readonly TemplateRenderService $templateRenderer,
    ) {}

    /**
     * Generate the lease PDF and return raw binary content.
     * Uses the same 3-strategy fallback chain as DownloadLeaseController.
     *
     * @throws Exception If all strategies fail
     */
    public function generate(Lease $lease): string
    {
        $lease->load(['tenant', 'unit', 'property', 'landlord', 'leaseTemplate', 'digitalSignatures']);
        $needsDraft = $this->needsDraftWatermark($lease);
        // Get the latest digital signature (if any) to embed in the PDF
        $digitalSignature = $lease->digitalSignatures->sortByDesc('created_at')->first();
        // Write signature to temp file so DomPDF can render it (data: URIs are not supported)
        $signatureImagePath = $digitalSignature ? $this->writeSignatureTempFile($digitalSignature) : null;

        try {
            // Strategy 1: Assigned custom template
            if ($lease->lease_template_id && $lease->leaseTemplate) {
                try {
                    $html = $this->renderTemplate($lease->leaseTemplate, $lease, $digitalSignature, $signatureImagePath);
                    if ($needsDraft) {
                        $html = $this->injectDraftWatermark($html);
                    }
                    $pdf = Pdf::loadHTML($html);
                    $this->setPdfMetadata($pdf, $lease);

                    return $pdf->output();
                } catch (Exception $e) {
                    Log::warning('LeasePdfService: custom template failed, trying default', [
                        'lease_id' => $lease->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

            // Strategy 2: Default template for lease type
            $defaultTemplate = LeaseTemplate::where('template_type', $lease->lease_type)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();

            if ($defaultTemplate) {
                try {
                    $html = $this->renderTemplate($defaultTemplate, $lease, $digitalSignature, $signatureImagePath);
                    if ($needsDraft) {
                        $html = $this->injectDraftWatermark($html);
                    }
                    $pdf = Pdf::loadHTML($html);
                    $this->setPdfMetadata($pdf, $lease);

                    return $pdf->output();
                } catch (Exception $e) {
                    Log::warning('LeasePdfService: default template failed, trying hardcoded view', [
                        'lease_id' => $lease->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

            // Strategy 3: Hardcoded Blade views
            $viewName = match ($lease->lease_type) {
                'residential_major' => 'pdf.residential-major',
                'residential_micro' => 'pdf.residential-micro',
                'commercial'        => 'pdf.commercial',
                default             => 'pdf.residential-major',
            };

            $data = [
                'lease'              => $lease,
                'tenant'             => $lease->tenant,
                'unit'               => $lease->unit,
                'landlord'           => $lease->landlord,
                'property'           => $lease->property,
                'today'              => now()->format('d/m/Y'),
                'digitalSignature'   => $digitalSignature ?? null,
                'signatureImagePath' => $signatureImagePath,
            ];

            $pdf = Pdf::loadView($viewName, $data);
            $this->setPdfMetadata($pdf, $lease);

            return $pdf->output();
        } finally {
            // Always clean up the temp signature file, regardless of which strategy succeeded or failed
            if ($signatureImagePath && file_exists($signatureImagePath)) {
                @unlink($signatureImagePath);
            }
        }
    }

    /**
     * Return a safe filename for the lease PDF.
     */
    public function filename(Lease $lease): string
    {
        return 'Lease-' . $lease->reference_number . '.pdf';
    }

    /**
     * Write the signature data URI to a temp PNG file and return its path.
     * DomPDF cannot render data: URIs directly — it needs a real file path.
     * File must be within DomPDF's chroot (base_path()), so we use storage/app/signatures/.
     * Caller is responsible for deleting the file after PDF generation.
     */
    public function writeSignatureTempFile(\App\Models\DigitalSignature $signature): ?string
    {
        try {
            $dataUri = $signature->data_uri;
            // Strip "data:image/png;base64," prefix
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $dataUri);
            $imageData = base64_decode($base64);

            if (! $imageData) {
                return null;
            }

            // Must be within base_path() due to DomPDF chroot restriction
            $dir = storage_path('app/signatures');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $path = $dir . '/sig_' . $signature->id . '_' . uniqid() . '.png';
            file_put_contents($path, $imageData);

            return $path;
        } catch (\Exception $e) {
            Log::warning('Could not write signature temp file', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function renderTemplate(
        LeaseTemplate $template,
        Lease $lease,
        ?\App\Models\DigitalSignature $digitalSignature = null,
        ?string $signatureImagePath = null,
    ): string {
        $html = $this->templateRenderer->render($template, $lease);

        if (empty(trim($html))) {
            throw new Exception('Template rendered empty HTML');
        }

        // Inject signature image into the rendered template HTML.
        // Blade template variables are not available inside LeaseTemplate blade_content,
        // so we inject the signature block immediately before the first </body> tag.
        if ($signatureImagePath && file_exists($signatureImagePath) && $digitalSignature) {
            $signedAt = $digitalSignature->created_at?->format('d M Y, h:i A') ?? '';
            $ip       = htmlspecialchars($digitalSignature->ip_address ?? 'N/A');

            $signatureBlock = '
<div style="margin-top:24px;padding:12px;border-top:2px solid #c8a020;">
  <p style="font-size:9pt;color:#333;margin:0 0 4px 0;"><strong>TENANT SIGNATURE</strong></p>
  <img src="' . $signatureImagePath . '" alt="Tenant Signature"
       style="max-width:220px;max-height:80px;border-bottom:1px solid #000;display:block;margin-bottom:4px;">
  <p style="font-size:8pt;color:#555;margin:0;">
    Digitally signed: ' . $signedAt . ' &nbsp;|&nbsp; IP: ' . $ip . '
  </p>
</div>';

            if (stripos($html, '</body>') !== false) {
                $html = str_ireplace('</body>', $signatureBlock . '</body>', $html);
            } else {
                $html .= $signatureBlock;
            }
        }

        return $html;
    }

    private function needsDraftWatermark(Lease $lease): bool
    {
        $state = $lease->workflow_state;

        if (is_string($state)) {
            $state = LeaseWorkflowState::tryFrom($state);
        }

        if (! $state instanceof LeaseWorkflowState) {
            return true;
        }

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

        return in_array($state, $draftStates, true);
    }

    private function setPdfMetadata(\Barryvdh\DomPDF\PDF $pdf, Lease $lease): void
    {
        $dompdf = $pdf->getDomPDF();
        $dompdf->addInfo('Title', 'Lease Agreement - ' . ($lease->reference_number ?? 'Draft'));
        $dompdf->addInfo('Author', 'Chabrin Agencies Ltd');
        $dompdf->addInfo('Creator', 'Chabrin Lease Management System');
        $dompdf->addInfo('Producer', 'Chabrin Agencies Ltd - DomPDF');
        $dompdf->addInfo('Subject', sprintf(
            '%s Lease - %s - %s',
            ucfirst(str_replace('_', ' ', $lease->lease_type ?? 'General')),
            $lease->tenant?->names ?? 'Unknown Tenant',
            $lease->property?->property_name ?? 'Unknown Property',
        ));
        $dompdf->addInfo('CreationDate', now()->format('D:YmdHis'));
    }

    private function injectDraftWatermark(string $html): string
    {
        $css = '<style>.draft-watermark{position:fixed;top:35%;left:10%;width:80%;text-align:center;z-index:-1;opacity:0.06;font-size:120pt;font-weight:bold;color:#cc0000;transform:rotate(-35deg);-webkit-transform:rotate(-35deg);letter-spacing:20px;pointer-events:none;}</style>';
        $div = '<div class="draft-watermark">DRAFT</div>';

        if (stripos($html, '</head>') !== false) {
            $html = str_ireplace('</head>', $css . '</head>', $html);
        } else {
            $html = $css . $html;
        }

        if (stripos($html, '<body>') !== false) {
            $html = str_ireplace('<body>', '<body>' . $div, $html);
        } elseif (stripos($html, '<body') !== false) {
            $html = preg_replace('/<body([^>]*)>/i', '<body$1>' . $div, $html);
        }

        return $html;
    }
}
