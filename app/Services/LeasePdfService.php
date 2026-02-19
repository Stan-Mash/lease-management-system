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

        // Strategy 1: Assigned custom template
        if ($lease->lease_template_id && $lease->leaseTemplate) {
            try {
                $html = $this->renderTemplate($lease->leaseTemplate, $lease);
                if ($needsDraft) {
                    $html = $this->injectDraftWatermark($html);
                }
                $pdf = Pdf::loadHTML($html);
                $this->setPdfMetadata($pdf, $lease);
                return $pdf->output();
            } catch (Exception $e) {
                Log::warning('LeasePdfService: custom template failed, trying default', [
                    'lease_id' => $lease->id,
                    'error' => $e->getMessage(),
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
                $html = $this->renderTemplate($defaultTemplate, $lease);
                if ($needsDraft) {
                    $html = $this->injectDraftWatermark($html);
                }
                $pdf = Pdf::loadHTML($html);
                $this->setPdfMetadata($pdf, $lease);
                return $pdf->output();
            } catch (Exception $e) {
                Log::warning('LeasePdfService: default template failed, trying hardcoded view', [
                    'lease_id' => $lease->id,
                    'error' => $e->getMessage(),
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
            'lease'             => $lease,
            'tenant'            => $lease->tenant,
            'unit'              => $lease->unit,
            'landlord'          => $lease->landlord,
            'property'          => $lease->property,
            'today'             => now()->format('d/m/Y'),
            'digitalSignature'  => $digitalSignature ?? null,
        ];

        $pdf = Pdf::loadView($viewName, $data);
        $this->setPdfMetadata($pdf, $lease);

        return $pdf->output();
    }

    /**
     * Return a safe filename for the lease PDF.
     */
    public function filename(Lease $lease): string
    {
        return 'Lease-' . $lease->reference_number . '.pdf';
    }

    private function renderTemplate(LeaseTemplate $template, Lease $lease): string
    {
        $html = $this->templateRenderer->render($template, $lease);

        if (empty(trim($html))) {
            throw new Exception('Template rendered empty HTML');
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
