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
        private readonly PdfOverlayService $pdfOverlay,
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

        // Tenant signature — the drawn canvas signature from the signing portal
        $tenantSignature = $lease->digitalSignatures->where('signer_type', 'tenant')->sortByDesc('created_at')->first()
            ?? $lease->digitalSignatures->sortByDesc('created_at')->first(); // fallback: any signature (legacy rows have no signer_type)
        $tenantSigPath = $tenantSignature ? $this->writeSignatureTempFile($tenantSignature) : null;

        // Manager countersignature — drawn by the property manager on countersign
        $managerSignature = $lease->digitalSignatures->where('signer_type', 'manager')->sortByDesc('created_at')->first();
        $managerSigPath = $managerSignature ? $this->writeSignatureTempFile($managerSignature) : null;

        // Legacy alias — keeps Strategy 3 Blade views working without change
        $digitalSignature = $tenantSignature;
        $signatureImagePath = $tenantSigPath;

        try {
            // Strategy 0: Landlord-provided PDF with coordinate map — stamp fields + signatures
            $template = $lease->leaseTemplate;
            if ($template && $template->source_pdf_path && $template->pdf_coordinate_map) {
                $sourcePath = storage_path('app/' . $template->source_pdf_path);
                if (! file_exists($sourcePath)) {
                    $sourcePath = storage_path('app/public/' . $template->source_pdf_path);
                }
                if (file_exists($sourcePath)) {
                    try {
                        $outDir = storage_path('app/lease-pdf-overlay');
                        if (! is_dir($outDir)) {
                            mkdir($outDir, 0755, true);
                        }
                        $baseName = 'lease-' . $lease->id . '-' . uniqid();
                        $step1 = $outDir . '/' . $baseName . '-fields.pdf';
                        $step2 = $outDir . '/' . $baseName . '-sig.pdf';
                        $step3 = $outDir . '/' . $baseName . '-final.pdf';

                        $fields = $this->overlayFieldsFromLease($lease);
                        $coordinates = $template->pdf_coordinate_map;
                        $textCoordinates = array_filter($coordinates, fn ($c, $k) => ! in_array((string) $k, ['tenant_signature', 'manager_signature'], true) && isset($c['x'], $c['y']), ARRAY_FILTER_USE_BOTH);

                        $this->pdfOverlay->stampFields($sourcePath, $fields, $textCoordinates, $step1);

                        $current = $step1;
                        if ($tenantSigPath && file_exists($tenantSigPath)) {
                            $coord = $coordinates['tenant_signature'] ?? ['page' => 1, 'x' => 140, 'y' => 240, 'width' => 50, 'height' => 20];
                            $this->pdfOverlay->stampSignature(
                                $current,
                                $tenantSigPath,
                                (int) ($coord['page'] ?? 1),
                                (float) ($coord['x'] ?? 140),
                                (float) ($coord['y'] ?? 240),
                                (float) ($coord['width'] ?? 50),
                                (float) ($coord['height'] ?? 20),
                                $step2,
                            );
                            @unlink($current);
                            $current = $step2;
                        }
                        if ($managerSigPath && file_exists($managerSigPath) && $managerSignature) {
                            $coord = $coordinates['manager_signature'] ?? ['page' => 1, 'x' => 140, 'y' => 260, 'width' => 50, 'height' => 20];
                            $next = $current === $step2 ? $step3 : $step2;
                            $this->pdfOverlay->stampSignature(
                                $current,
                                $managerSigPath,
                                (int) ($coord['page'] ?? 1),
                                (float) ($coord['x'] ?? 140),
                                (float) ($coord['y'] ?? 260),
                                (float) ($coord['width'] ?? 50),
                                (float) ($coord['height'] ?? 20),
                                $next,
                            );
                            if ($current !== $step1) {
                                @unlink($current);
                            }
                            $current = $next;
                        }
                        $auditPath = $outDir . '/' . $baseName . '-audit.pdf';
                        if ($tenantSignature && $managerSignature) {
                            $this->pdfOverlay->stampAuditBlock($current, $lease, $tenantSignature, $managerSignature, $auditPath);
                            if ($current !== $step1) {
                                @unlink($current);
                            }
                            $current = $auditPath;
                        }

                        $binary = file_get_contents($current);
                        foreach ([$step1, $step2, $step3, $auditPath] as $f) {
                            if (file_exists($f)) {
                                @unlink($f);
                            }
                        }

                        if ($binary !== false) {
                            return $binary;
                        }
                    } catch (Exception $e) {
                        Log::warning('LeasePdfService: PDF overlay strategy failed, falling back to template', [
                            'lease_id' => $lease->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Strategy 1: Assigned custom template
            if ($lease->lease_template_id && $lease->leaseTemplate) {
                try {
                    $html = $this->renderTemplate($lease->leaseTemplate, $lease, $tenantSignature, $tenantSigPath, $managerSignature, $managerSigPath);
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
                    $html = $this->renderTemplate($defaultTemplate, $lease, $tenantSignature, $tenantSigPath, $managerSignature, $managerSigPath);
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
                'commercial' => 'pdf.commercial',
                default => 'pdf.residential-major',
            };

            $data = [
                'lease' => $lease,
                'tenant' => $lease->tenant,
                'unit' => $lease->unit,
                'landlord' => $lease->landlord,
                'property' => $lease->property,
                'today' => now()->format('d/m/Y'),
                // Tenant signature (legacy variable name kept for backward compat with Blade views)
                'digitalSignature' => $tenantSignature,
                'signatureImagePath' => $tenantSigPath,
                // Manager countersignature
                'managerSignature' => $managerSignature,
                'managerSigPath' => $managerSigPath,
            ];

            $pdf = Pdf::loadView($viewName, $data);
            $this->setPdfMetadata($pdf, $lease);

            return $pdf->output();
        } finally {
            // Always clean up both temp signature files
            if ($tenantSigPath && file_exists($tenantSigPath)) {
                @unlink($tenantSigPath);
            }
            if ($managerSigPath && file_exists($managerSigPath)) {
                @unlink($managerSigPath);
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
        } catch (Exception $e) {
            Log::warning('Could not write signature temp file', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function renderTemplate(
        LeaseTemplate $template,
        Lease $lease,
        ?\App\Models\DigitalSignature $tenantSignature = null,
        ?string $tenantSigPath = null,
        ?\App\Models\DigitalSignature $managerSignature = null,
        ?string $managerSigPath = null,
    ): string {
        $html = $this->templateRenderer->render($template, $lease);

        if (empty(trim($html))) {
            throw new Exception('Template rendered empty HTML');
        }

        // Build the signature block to inject before </body>.
        // We render both parties side-by-side if both have signed,
        // or just the tenant if the manager hasn't countersigned yet.
        $sigBlock = '';

        $hasTenant = $tenantSigPath && file_exists($tenantSigPath) && $tenantSignature;
        $hasManager = $managerSigPath && file_exists($managerSigPath) && $managerSignature;

        if ($hasTenant || $hasManager) {
            $sigBlock .= '<div style="margin-top:24px;padding:12px 0;border-top:2px solid #c8a020;">';
            $sigBlock .= '<table style="width:100%;border-collapse:collapse;">';
            $sigBlock .= '<tr>';

            // ── Tenant signature cell ──
            $sigBlock .= '<td style="width:50%;vertical-align:top;padding-right:12px;">';
            $sigBlock .= '<p style="font-size:9pt;color:#333;margin:0 0 4px 0;"><strong>TENANT SIGNATURE</strong></p>';
            if ($hasTenant) {
                $tenantSignedAt = $tenantSignature->created_at?->format('d M Y, h:i A') ?? '';
                $tenantIp = htmlspecialchars($tenantSignature->ip_address ?? 'N/A');
                $sigBlock .= '<img src="' . $tenantSigPath . '" alt="Tenant Signature"
                    style="max-width:200px;max-height:70px;border-bottom:1px solid #000;display:block;margin-bottom:4px;">';
                $sigBlock .= '<p style="font-size:8pt;color:#555;margin:0;">Signed: ' . $tenantSignedAt . '<br>IP: ' . $tenantIp . '</p>';
            } else {
                $sigBlock .= '<div style="border-bottom:1px solid #000;height:50px;margin-bottom:4px;"></div>';
                $sigBlock .= '<p style="font-size:8pt;color:#aaa;margin:0;">Not yet signed</p>';
            }
            $sigBlock .= '</td>';

            // ── Manager countersignature cell ──
            $sigBlock .= '<td style="width:50%;vertical-align:top;padding-left:12px;border-left:1px solid #e0e0e0;">';
            $sigBlock .= '<p style="font-size:9pt;color:#333;margin:0 0 4px 0;"><strong>PROPERTY MANAGER SIGNATURE</strong></p>';
            if ($hasManager) {
                $mgrSignedAt = $managerSignature->created_at?->format('d M Y, h:i A') ?? '';
                $mgrName = htmlspecialchars($managerSignature->signed_by_name ?? 'Property Manager');
                $mgrIp = htmlspecialchars($managerSignature->ip_address ?? 'N/A');
                $sigBlock .= '<img src="' . $managerSigPath . '" alt="Manager Signature"
                    style="max-width:200px;max-height:70px;border-bottom:1px solid #000;display:block;margin-bottom:4px;">';
                $sigBlock .= '<p style="font-size:8pt;color:#555;margin:0;">' . $mgrName . '<br>Signed: ' . $mgrSignedAt . '<br>IP: ' . $mgrIp . '</p>';
            } else {
                $sigBlock .= '<div style="border-bottom:1px solid #000;height:50px;margin-bottom:4px;"></div>';
                $sigBlock .= '<p style="font-size:8pt;color:#aaa;margin:0;">Pending countersignature</p>';
            }
            $sigBlock .= '</td>';

            $sigBlock .= '</tr></table></div>';
        }

        if ($sigBlock !== '') {
            if (stripos($html, '</body>') !== false) {
                $html = str_ireplace('</body>', $sigBlock . '</body>', $html);
            } else {
                $html .= $sigBlock;
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

    /**
     * Build merge fields for PDF overlay (Strategy 0).
     *
     * @return array<string, string>
     */
    private function overlayFieldsFromLease(Lease $lease): array
    {
        return [
            'tenant_name' => $lease->tenant?->names ?? '',
            'unit_code' => $lease->unit_code ?? $lease->unit?->unit_code ?? '',
            'property_name' => $lease->property?->property_name ?? '',
            'monthly_rent' => $lease->monthly_rent ? number_format((float) $lease->monthly_rent, 2) : '',
            'start_date' => $lease->start_date?->format('d/m/Y') ?? '',
            'end_date' => $lease->end_date?->format('d/m/Y') ?? '',
            'landlord_name' => $lease->landlord?->names ?? '',
            'reference_number' => $lease->reference_number ?? '',
        ];
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
