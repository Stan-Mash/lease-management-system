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
     * Generate PDF preview for a template with sample data. Uses uploaded PDF when available.
     *
     * @param  array{lease: object, tenant: object, landlord: object, property: object, unit: object}  $sampleData
     * @param  string|null  $resolvedSourcePath  Full path to source PDF (if already resolved)
     *
     * @throws Exception If PDF generation fails
     */
    public function generateForPreview(LeaseTemplate $template, array $sampleData, ?string $resolvedSourcePath = null): string
    {
        $sourcePath = $resolvedSourcePath;
        if (! $sourcePath && $template->source_pdf_path) {
            $sourcePath = storage_path('app/public/' . $template->source_pdf_path);
            if (! file_exists($sourcePath)) {
                $sourcePath = storage_path('app/' . $template->source_pdf_path);
            }
            if (! file_exists($sourcePath)) {
                $sourcePath = storage_path('app/private/' . $template->source_pdf_path);
            }
        }
        if ($sourcePath && file_exists($sourcePath)) {
                $fields = $this->overlayFieldsFromSample($sampleData);
                $coordinates = $template->pdf_coordinate_map ?? [];
                $sigKeys = ['tenant_signature', 'manager_signature', 'witness_signature', 'advocate_signature', 'guarantor_signature'];
                $textCoordinates = is_array($coordinates)
                    ? array_filter($coordinates, fn ($c, $k) => ! in_array((string) $k, $sigKeys, true) && isset($c['x'], $c['y']), ARRAY_FILTER_USE_BOTH)
                    : [];

                $outDir = storage_path('app/lease-pdf-overlay');
                if (! is_dir($outDir)) {
                    mkdir($outDir, 0755, true);
                }
                $outputPath = $outDir . '/preview-' . $template->id . '-' . uniqid() . '.pdf';
                $this->pdfOverlay->stampFields($sourcePath, $fields, $textCoordinates, $outputPath);
                $binary = file_get_contents($outputPath);
                @unlink($outputPath);

                if ($binary !== false) {
                    return $binary;
                }
        }

        throw new Exception('No uploaded PDF found. Upload your PDF on the Lease Template PDF Upload tab.');
    }

    /**
     * Generate the lease PDF and return raw binary content.
     * Uses the same 3-strategy fallback chain as DownloadLeaseController.
     *
     * @throws Exception If all strategies fail
     */
    public function generate(Lease $lease): string
    {
        $lease->load(['tenant', 'unit', 'property', 'landlord', 'leaseTemplate', 'digitalSignatures', 'witnesses', 'guarantors']);
        $needsDraft = $this->needsDraftWatermark($lease);

        // Tenant signature — the drawn canvas signature from the signing portal
        $tenantSignature = $lease->digitalSignatures->where('signer_type', 'tenant')->sortByDesc('created_at')->first()
            ?? $lease->digitalSignatures->sortByDesc('created_at')->first(); // fallback: any signature (legacy rows have no signer_type)
        $tenantSigPath = $tenantSignature ? $this->writeSignatureTempFile($tenantSignature) : null;

        // Manager/Lessor countersignature — drawn by the property manager on countersign
        $managerSignature = $lease->digitalSignatures->where('signer_type', 'manager')->sortByDesc('created_at')->first();
        $managerSigPath = $managerSignature ? $this->writeSignatureTempFile($managerSignature) : null;

        // Witness & Advocate — from DigitalSignature when signer_type is set
        $witnessSignature = $lease->digitalSignatures->where('signer_type', 'witness')->sortByDesc('created_at')->first();
        $witnessSigPath = $witnessSignature ? $this->writeSignatureTempFile($witnessSignature) : null;
        $advocateSignature = $lease->digitalSignatures->where('signer_type', 'advocate')->sortByDesc('created_at')->first();
        $advocateSigPath = $advocateSignature ? $this->writeSignatureTempFile($advocateSignature) : null;

        // Guarantor(s) — from Guarantor model signature_path (storage path)
        $guarantorSigPaths = [];
        foreach ($lease->guarantors->whereNotNull('signature_path') as $guarantor) {
            $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($guarantor->signature_path);
            if (file_exists($fullPath)) {
                $guarantorSigPaths[] = $fullPath;
            }
        }

        // Legacy alias — keeps Strategy 3 Blade views working without change
        $digitalSignature = $tenantSignature;
        $signatureImagePath = $tenantSigPath;

        try {
            // Strategy 0: Landlord-provided PDF — use uploaded PDF as base, stamp fields + signatures.
            // Only used when a coordinate map has been configured; without coordinates the stamping
            // produces a blank template with no lease data, so we fall through to Strategy 1 instead.
            $template = $lease->leaseTemplate;
            $coordinates = $template?->pdf_coordinate_map ?? [];
            $hasCoordinates = is_array($coordinates) && count($coordinates) > 0;

            if ($template && $template->source_pdf_path && $hasCoordinates) {
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
                        $coordinates = $template->pdf_coordinate_map ?? [];
                        $sigKeys = ['tenant_signature', 'manager_signature', 'witness_signature', 'advocate_signature', 'guarantor_signature'];
                        $textCoordinates = is_array($coordinates)
                            ? array_filter($coordinates, fn ($c, $k) => ! in_array((string) $k, $sigKeys, true) && isset($c['x'], $c['y']), ARRAY_FILTER_USE_BOTH)
                            : [];

                        $this->pdfOverlay->stampFields($sourcePath, $fields, $textCoordinates, $step1);

                        $current = $step1;
                        if ($tenantSigPath && file_exists($tenantSigPath) && is_array($coordinates) && isset($coordinates['tenant_signature'])) {
                            $coord = $coordinates['tenant_signature'];
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
                        if ($managerSigPath && file_exists($managerSigPath) && $managerSignature && is_array($coordinates) && isset($coordinates['manager_signature'])) {
                            $coord = $coordinates['manager_signature'];
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
                                (string) ($coord['anchor'] ?? 'above'),
                            );
                            if ($current !== $step1) {
                                @unlink($current);
                            }
                            $current = $next;
                        }
                        $step4 = $outDir . '/' . $baseName . '-sig4.pdf';
                        if ($advocateSigPath && file_exists($advocateSigPath) && is_array($coordinates) && isset($coordinates['advocate_signature'])) {
                            $coord = $coordinates['advocate_signature'];
                            $this->pdfOverlay->stampSignature(
                                $current,
                                $advocateSigPath,
                                (int) ($coord['page'] ?? 1),
                                (float) ($coord['x'] ?? 160),
                                (float) ($coord['y'] ?? 250),
                                (float) ($coord['width'] ?? 45),
                                (float) ($coord['height'] ?? 18),
                                $step4,
                                (string) ($coord['anchor'] ?? 'beside'),
                            );
                            if ($current !== $step1) {
                                @unlink($current);
                            }
                            $current = $step4;
                        }
                        if ($witnessSigPath && file_exists($witnessSigPath) && is_array($coordinates) && isset($coordinates['witness_signature'])) {
                            $coord = $coordinates['witness_signature'];
                            $next = $outDir . '/' . $baseName . '-witness.pdf';
                            $this->pdfOverlay->stampSignature(
                                $current,
                                $witnessSigPath,
                                (int) ($coord['page'] ?? 1),
                                (float) ($coord['x'] ?? 140),
                                (float) ($coord['y'] ?? 235),
                                (float) ($coord['width'] ?? 50),
                                (float) ($coord['height'] ?? 20),
                                $next,
                                'default',
                            );
                            if ($current !== $step1 && $current !== $step2 && $current !== $step3 && file_exists($current)) {
                                @unlink($current);
                            }
                            $current = $next;
                        }
                        $guarantorSigPath = $guarantorSigPaths[0] ?? null;
                        if ($guarantorSigPath && file_exists($guarantorSigPath) && is_array($coordinates) && isset($coordinates['guarantor_signature'])) {
                            $coord = $coordinates['guarantor_signature'];
                            $next = $outDir . '/' . $baseName . '-guarantor.pdf';
                            $this->pdfOverlay->stampSignature(
                                $current,
                                $guarantorSigPath,
                                (int) ($coord['page'] ?? 1),
                                (float) ($coord['x'] ?? 140),
                                (float) ($coord['y'] ?? 220),
                                (float) ($coord['width'] ?? 50),
                                (float) ($coord['height'] ?? 20),
                                $next,
                                'default',
                            );
                            if ($current !== $step1 && $current !== $step2 && $current !== $step3 && file_exists($current)) {
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
                        foreach (glob($outDir . '/' . $baseName . '*.pdf') ?: [] as $f) {
                            if (file_exists($f)) {
                                @unlink($f);
                            }
                        }

                        if ($binary !== false) {
                            Log::info('LeasePdfService: Strategy 0 (uploaded PDF) succeeded', ['lease_id' => $lease->id]);

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

            // Strategy 2: Default template for lease type (fallback when assigned template fails)
            $defaultTemplate = LeaseTemplate::where('template_type', $lease->lease_type)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();

            if ($defaultTemplate) {
                $html = $this->renderTemplate($defaultTemplate, $lease, $tenantSignature, $tenantSigPath, $managerSignature, $managerSigPath);
                if ($needsDraft) {
                    $html = $this->injectDraftWatermark($html);
                }
                $pdf = Pdf::loadHTML($html);
                $this->setPdfMetadata($pdf, $lease);

                return $pdf->output();
            }

            throw new Exception(
                "No template found for lease {$lease->reference_number} (type: {$lease->lease_type}). " .
                'Assign a template to this lease or mark one as default for this lease type.'
            );
        } finally {
            foreach (array_filter([$tenantSigPath, $managerSigPath, $witnessSigPath ?? null, $advocateSigPath ?? null]) as $p) {
                if ($p && file_exists($p)) {
                    @unlink($p);
                }
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
        $dompdf->addInfo('Creator', 'Chabrin Agencies');
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
     * Build overlay fields from sample data (for preview).
     *
     * @param  array{lease: object, tenant: object, landlord: object, property: object, unit: object}  $sampleData
     *
     * @return array<string, string>
     */
    private function overlayFieldsFromSample(array $sampleData): array
    {
        $lease = $sampleData['lease'];
        $tenant = $sampleData['tenant'];
        $landlord = $sampleData['landlord'];
        $property = $sampleData['property'];
        $unit = $sampleData['unit'];

        $startDate = $lease->start_date ?? null;
        $endDate   = $lease->end_date ?? null;
        $rent      = $lease->monthly_rent ? (float) $lease->monthly_rent : null;
        $deposit   = $lease->deposit_amount ? (float) $lease->deposit_amount : null;

        $sd = $startDate ? (is_object($startDate) ? $startDate : \Carbon\Carbon::parse($startDate)) : \Carbon\Carbon::parse('2026-03-07');
        $ed = $endDate ? (is_object($endDate) ? $endDate : \Carbon\Carbon::parse($endDate)) : \Carbon\Carbon::parse('2027-03-07');

        return [
            'lease_date_day'   => $sd->format('d'),
            'lease_date_month' => $sd->format('F'),
            'lease_date_year'  => $sd->format('Y'),
            'start_date_day'   => $sd->format('d'),
            'start_date_month' => $sd->format('m'),
            'start_date_year'  => $sd->format('Y'),
            'end_date_day'     => $ed->format('d'),
            'end_date_month'   => $ed->format('m'),
            'end_date_year'    => $ed->format('Y'),
            'landlord_name'   => $landlord->names ?? $landlord->name ?? 'Creek View Limited',
            'landlord_po_box' => $landlord->po_box ?? '',
            'tenant_name'     => $tenant->names ?? $tenant->full_name ?? 'John Doe',
            'tenant_id_number' => $tenant->national_id ?? $tenant->id_number ?? '12345678',
            'tenant_po_box'   => $tenant->po_box ?? '',
            'property_name'      => $property->property_name ?? $property->name ?? 'Sample Building',
            'property_lr_number' => $property->lr_number ?? 'LR/12345/678',
            'unit_code'          => $unit->unit_code ?? $unit->unit_number ?? 'A-101',
            'start_date'            => $sd->format('d-m-Y'),
            'end_date'              => $ed->format('d-m-Y'),
            'lease_duration_months' => '5 year(s) 3 month(s)',
            'monthly_rent'   => $rent ? number_format($rent, 2) : '50,000.00',
            'deposit_amount' => $deposit ? number_format($deposit, 2) : '100,000.00',
            'vat_amount'     => $rent ? number_format($rent * 0.16, 2) : '8,000.00',
            'rent_review_years' => '1',
            'rent_review_rate'  => '5.0',
            'reference_number' => $lease->reference_number ?? 'CH-COM-SAMPLE-2026',
        ];
    }

    /**
     * Build merge fields for PDF overlay (Strategy 0).
     * All blank strings are skipped by PdfOverlayService (template blanks stay visible).
     *
     * @return array<string, string>
     */
    private function overlayFieldsFromLease(Lease $lease): array
    {
        $startDate  = $lease->start_date;
        $endDate    = $lease->end_date;
        $rent       = $lease->monthly_rent ? (float) $lease->monthly_rent : null;
        $deposit    = $lease->deposit_amount ? (float) $lease->deposit_amount : null;
        $termMonths = $lease->lease_term_months;

        // Auto-compute end date from start + term if end_date not set
        if ($startDate && ! $endDate && $termMonths) {
            $endDate = $startDate->copy()->addMonths((int) $termMonths);
        }

        // VAT at 16% (Kenya statutory rate)
        $vatAmount = $rent ? round($rent * 0.16, 2) : null;

        // Lease duration label
        $durationLabel = '';
        if ($termMonths) {
            $years  = intdiv((int) $termMonths, 12);
            $months = (int) $termMonths % 12;
            if ($years && $months) {
                $durationLabel = "{$years} year(s) {$months} month(s)";
            } elseif ($years) {
                $durationLabel = "{$years} year(s)";
            } else {
                $durationLabel = "{$months} month(s)";
            }
        }

        return [
            // Date at top: "dated the __ day on the month of __ in the year __" (no slashes; document has its own separators)
            'lease_date_day'   => $startDate ? $startDate->format('d') : '',
            'lease_date_month' => $startDate ? $startDate->format('F') : '',
            'lease_date_year'  => $startDate ? $startDate->format('Y') : '',

            // Term "from __ / __ / __ To __ / __ / __" — separate day/month/year so slashes stay on the form
            'start_date_day'   => $startDate ? $startDate->format('d') : '',
            'start_date_month' => $startDate ? $startDate->format('m') : '',
            'start_date_year'  => $startDate ? $startDate->format('Y') : '',
            'end_date_day'     => $endDate ? $endDate->format('d') : '',
            'end_date_month'   => $endDate ? $endDate->format('m') : '',
            'end_date_year'    => $endDate ? $endDate->format('Y') : '',

            // Parties
            'landlord_name'   => $lease->landlord?->names ?? '',
            'landlord_po_box' => $lease->landlord?->po_box ?? '',
            'tenant_name'     => $lease->tenant?->names ?? '',
            'tenant_id_number' => $lease->tenant?->national_id ?? $lease->tenant?->passport_number ?? '',
            'tenant_po_box'   => $lease->tenant?->po_box ?? '',

            // Property
            'property_name'      => $lease->property?->property_name ?? '',
            'property_lr_number' => $lease->property?->lr_number ?? '',
            'unit_code'          => $lease->unit_code ?? $lease->unit?->unit_code ?? '',

            // Term (single-field fallback: no slashes; document may already show separators)
            'start_date'            => $startDate ? $startDate->format('d-m-Y') : '',
            'end_date'              => $endDate ? $endDate->format('d-m-Y') : '',
            'lease_duration_months' => $durationLabel,

            // Financials
            'monthly_rent'     => $rent ? number_format($rent, 2) : '',
            'deposit_amount'   => $deposit ? number_format($deposit, 2) : '',
            'vat_amount'       => $vatAmount ? number_format($vatAmount, 2) : '',

            // Rent review (optional — set on lease when creating)
            'rent_review_years' => $lease->rent_review_years ? (string) $lease->rent_review_years : '',
            'rent_review_rate'  => $lease->rent_review_rate ? number_format((float) $lease->rent_review_rate, 1) : '',

            // Reference (for any reference fields in template)
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
