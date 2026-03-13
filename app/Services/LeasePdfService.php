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
        $lease->load(['tenant', 'unit', 'property', 'landlord', 'leaseTemplate', 'digitalSignatures', 'witnesses', 'guarantors', 'lawyerTrackings.lawyer', 'zoneManager']);
        $needsDraft = $this->needsDraftWatermark($lease);

        // Tenant / Lessee signature — drawn canvas from signing portal
        $tenantSignature = $lease->digitalSignatures->where('signer_type', 'tenant')->sortByDesc('created_at')->first()
            ?? $lease->digitalSignatures->sortByDesc('created_at')->first(); // fallback: legacy rows with no signer_type
        $tenantSigPath = $tenantSignature ? $this->writeSignatureTempFile($tenantSignature) : null;

        // Manager / Lessor countersignature
        $managerSignature = $lease->digitalSignatures->where('signer_type', 'manager')->sortByDesc('created_at')->first();
        $managerSigPath = $managerSignature ? $this->writeSignatureTempFile($managerSignature) : null;

        // Lessee witness — may come from DigitalSignature OR LeaseWitness model
        $witnessTempSigPath = null;
        $witnessSigPath = null;
        $witnessSignature = $lease->digitalSignatures->where('signer_type', 'witness')->sortByDesc('created_at')->first();
        if ($witnessSignature) {
            $witnessTempSigPath = $this->writeSignatureTempFile($witnessSignature);
            $witnessSigPath = $witnessTempSigPath;
        } else {
            $witnessModelPath = $lease->witnesses
                ->where('witnessed_party', 'tenant')
                ->sortByDesc('witnessed_at')
                ->value('witness_signature_path');
            if ($witnessModelPath) {
                $fullPath = storage_path('app/' . $witnessModelPath);
                if (file_exists($fullPath)) {
                    $witnessSigPath = $fullPath;
                }
            }
        }

        // Legacy advocate signature — signer_type='advocate' (old single-advocate workflow)
        $advocateSignature = $lease->digitalSignatures->where('signer_type', 'advocate')->sortByDesc('created_at')->first();
        $advocateSigPath = $advocateSignature ? $this->writeSignatureTempFile($advocateSignature) : null;

        // ── New dual-advocate signatures ─────────────────────────────────────
        // Lessor advocate — signer_type='lessor_advocate'
        $lessorAdvSig = $lease->digitalSignatures->where('signer_type', 'lessor_advocate')->sortByDesc('created_at')->first();
        $lessorAdvSigPath = $lessorAdvSig ? $this->writeSignatureTempFile($lessorAdvSig) : null;

        // Lessee advocate — signer_type='lessee_advocate'; fall back to legacy 'advocate'
        $lesseeAdvSig = $lease->digitalSignatures->where('signer_type', 'lessee_advocate')->sortByDesc('created_at')->first()
            ?? $advocateSignature;
        $lesseeAdvSigPath = ($lesseeAdvSig && $lesseeAdvSig !== $advocateSignature)
            ? $this->writeSignatureTempFile($lesseeAdvSig)
            : $advocateSigPath; // reuse already-extracted path for legacy advocate

        // Lessor witness — from LeaseWitness where witnessed_party='lessor'
        $lessorWitnessModel = $lease->witnesses->where('witnessed_party', 'lessor')->sortByDesc('witnessed_at')->first();
        $lessorWitnessSigPath = null;
        if ($lessorWitnessModel?->witness_signature_path) {
            $fullPath = storage_path('app/' . $lessorWitnessModel->witness_signature_path);
            if (file_exists($fullPath)) {
                $lessorWitnessSigPath = $fullPath;
            }
        }

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
            $template = $lease->leaseTemplate;
            $coordinates = $template?->pdf_coordinate_map ?? [];
            $hasCoordinates = is_array($coordinates) && count($coordinates) > 0;

            // Strategy 0a: Lawyer-stamped document as base.
            // When the advocate has uploaded via the portal, their signature AND stamp are already
            // baked into the stored lawyer_stamped PDF. Use it as the base and overlay only the
            // remaining signatures (manager, witness) on top — the advocate stamp is preserved.
            $lawyerStampedDoc = $lease->documents()->where('document_type', 'lawyer_stamped')->latest()->first();
            if ($lawyerStampedDoc) {
                $lawyerDocPath = \Illuminate\Support\Facades\Storage::disk('local')->path($lawyerStampedDoc->file_path);
                if (file_exists($lawyerDocPath)) {
                    try {
                        $outDir = storage_path('app/lease-pdf-overlay');
                        if (! is_dir($outDir)) {
                            mkdir($outDir, 0755, true);
                        }
                        $baseName = 'lease-' . $lease->id . '-ls-' . uniqid();
                        $current  = $lawyerDocPath;

                        // Manager signature — use coordinate map or sensible defaults
                        if ($managerSigPath && file_exists($managerSigPath) && $managerSignature) {
                            $coord = is_array($coordinates) && isset($coordinates['manager_signature'])
                                ? $coordinates['manager_signature']
                                : ['page' => 2, 'x' => 140, 'y' => 280, 'width' => 80, 'height' => 30, 'anchor' => 'above'];
                            $next  = $outDir . '/' . $baseName . '-mgr.pdf';
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
                            if ($current !== $lawyerDocPath) {
                                @unlink($current);
                            }
                            $current = $next;
                        }

                        // Witness signature — use coordinate map or sensible defaults
                        if ($witnessSigPath && file_exists($witnessSigPath)) {
                            $coord = is_array($coordinates) && isset($coordinates['witness_signature'])
                                ? $coordinates['witness_signature']
                                : ['page' => 2, 'x' => 20, 'y' => 260, 'width' => 50, 'height' => 20];
                            $next  = $outDir . '/' . $baseName . '-witness.pdf';
                            $this->pdfOverlay->stampSignature(
                                $current,
                                $witnessSigPath,
                                (int) ($coord['page'] ?? 1),
                                (float) ($coord['x'] ?? 20),
                                (float) ($coord['y'] ?? 260),
                                (float) ($coord['width'] ?? 50),
                                (float) ($coord['height'] ?? 20),
                                $next,
                                'default',
                            );
                            if ($current !== $lawyerDocPath) {
                                @unlink($current);
                            }
                            $current = $next;
                        }

                        // ── Date texts next to each signature ──────────────────
                        $dateEntries = $this->buildSignatureDateEntries(
                            $lease, $coordinates,
                            $tenantSignature, $managerSignature,
                            $witnessSignature ?? null,
                            $advocateSignature ?? null,
                        );
                        if (! empty($dateEntries)) {
                            $next = $outDir . '/' . $baseName . '-dates.pdf';
                            $this->pdfOverlay->stampDateTexts($current, $dateEntries, $next);
                            if ($current !== $lawyerDocPath) {
                                @unlink($current);
                            }
                            $current = $next;
                        }

                        // ── New-style 6-box signing-page overlay (text + additional sigs) ──
                        $newStyleCoords = $this->filterNewStyleSigningCoords(is_array($coordinates) ? $coordinates : []);
                        if (! empty($newStyleCoords)) {
                            $next = $outDir . '/' . $baseName . '-signing.pdf';
                            $this->pdfOverlay->stampAllSigningFields(
                                $current,
                                $this->signingPageTextFields($lease),
                                array_filter([
                                    'lessor_signature'          => $managerSigPath,
                                    'lessor_witness_signature'  => $lessorWitnessSigPath,
                                    'lessor_advocate_signature' => $lessorAdvSigPath,
                                    'lessee_signature'          => $tenantSigPath,
                                    'lessee_witness_signature'  => $witnessSigPath,
                                    'lessee_advocate_signature' => $lesseeAdvSigPath,
                                ]),
                                $newStyleCoords,
                                $next,
                            );
                            if ($current !== $lawyerDocPath) {
                                @unlink($current);
                            }
                            $current = $next;
                        }

                        if ($tenantSignature && $managerSignature) {
                            $auditPath = $outDir . '/' . $baseName . '-audit.pdf';
                            $this->pdfOverlay->stampAuditBlock($current, $lease, $tenantSignature, $managerSignature, $auditPath);
                            if ($current !== $lawyerDocPath) {
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
                            Log::info('LeasePdfService: Strategy 0a (lawyer-stamped base) succeeded', ['lease_id' => $lease->id]);

                            return $binary;
                        }
                    } catch (Exception $e) {
                        Log::warning('LeasePdfService: Strategy 0a failed, falling back', [
                            'lease_id' => $lease->id,
                            'error'    => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Strategy 0: Landlord-provided PDF — use uploaded PDF as base, stamp fields + signatures.
            // Only used when a coordinate map has been configured; without coordinates the stamping
            // produces a blank template with no lease data, so we fall through to Strategy 1 instead.
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
                        // Witness signature — use coordinate map or defaults
                        if ($witnessSigPath && file_exists($witnessSigPath)) {
                            $coord = is_array($coordinates) && isset($coordinates['witness_signature'])
                                ? $coordinates['witness_signature']
                                : ['page' => 2, 'x' => 20, 'y' => 260, 'width' => 50, 'height' => 20];
                            $next = $outDir . '/' . $baseName . '-witness.pdf';
                            $this->pdfOverlay->stampSignature(
                                $current,
                                $witnessSigPath,
                                (int) ($coord['page'] ?? 1),
                                (float) ($coord['x'] ?? 20),
                                (float) ($coord['y'] ?? 260),
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

                        // ── Date texts next to each signature ──────────────────
                        $dateEntries = $this->buildSignatureDateEntries(
                            $lease, $coordinates,
                            $tenantSignature, $managerSignature,
                            $witnessSignature ?? null,
                            $advocateSignature ?? null,
                        );
                        if (! empty($dateEntries)) {
                            $next = $outDir . '/' . $baseName . '-dates.pdf';
                            $this->pdfOverlay->stampDateTexts($current, $dateEntries, $next);
                            if ($current !== $step1 && file_exists($current)) {
                                @unlink($current);
                            }
                            $current = $next;
                        }

                        // ── New-style 6-box signing-page overlay (text + additional sigs) ──
                        $newStyleCoords = $this->filterNewStyleSigningCoords(is_array($coordinates) ? $coordinates : []);
                        if (! empty($newStyleCoords)) {
                            $next = $outDir . '/' . $baseName . '-signing.pdf';
                            $this->pdfOverlay->stampAllSigningFields(
                                $current,
                                $this->signingPageTextFields($lease),
                                array_filter([
                                    'lessor_signature'          => $managerSigPath,
                                    'lessor_witness_signature'  => $lessorWitnessSigPath,
                                    'lessor_advocate_signature' => $lessorAdvSigPath,
                                    'lessee_signature'          => $tenantSigPath,
                                    'lessee_witness_signature'  => $witnessSigPath,
                                    'lessee_advocate_signature' => $lesseeAdvSigPath,
                                ]),
                                $newStyleCoords,
                                $next,
                            );
                            if ($current !== $step1 && file_exists($current)) {
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
                    $html = $this->renderTemplate(
                        $lease->leaseTemplate, $lease,
                        $tenantSignature, $tenantSigPath,
                        $managerSignature, $managerSigPath,
                        $witnessSignature ?? null, $witnessSigPath,
                        $advocateSignature ?? null, $advocateSigPath,
                    );
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
                $html = $this->renderTemplate(
                    $defaultTemplate, $lease,
                    $tenantSignature, $tenantSigPath,
                    $managerSignature, $managerSigPath,
                    $witnessSignature ?? null, $witnessSigPath,
                    $advocateSignature ?? null, $advocateSigPath,
                );
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
            $tempPaths = array_filter([
                $tenantSigPath,
                $managerSigPath,
                $witnessTempSigPath ?? null,
                $advocateSigPath ?? null,
                $lessorAdvSigPath ?? null,
                // $lesseeAdvSigPath may equal $advocateSigPath (reuse), avoid double-unlink
                ($lessorAdvSigPath !== null && $lesseeAdvSigPath !== $advocateSigPath) ? $lesseeAdvSigPath : null,
            ]);
            foreach ($tempPaths as $p) {
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
        ?\App\Models\DigitalSignature $witnessSignature = null,
        ?string $witnessSigPath = null,
        ?\App\Models\DigitalSignature $advocateSignature = null,
        ?string $advocateSigPath = null,
    ): string {
        $html = $this->templateRenderer->render($template, $lease);

        if (empty(trim($html))) {
            throw new Exception('Template rendered empty HTML');
        }

        // Build the signature block to inject before </body>.
        // Main parties side-by-side (top row), then witness + advocate below.
        $sigBlock = '';

        $hasTenant   = $tenantSigPath && file_exists($tenantSigPath) && $tenantSignature;
        $hasManager  = $managerSigPath && file_exists($managerSigPath) && $managerSignature;
        $hasWitness  = $witnessSigPath && file_exists($witnessSigPath);
        $hasAdvocate = $advocateSigPath && file_exists($advocateSigPath) && $advocateSignature;

        // Also check LeaseWitness model when no DigitalSignature witness exists
        $witnessModelPath = null;
        $witnessModelDate = null;
        if (! $hasWitness) {
            $witnessModel = $lease->witnesses
                ->where('witnessed_party', 'tenant')
                ->sortByDesc('witnessed_at')
                ->first();
            if ($witnessModel?->witness_signature_path) {
                $fullPath = storage_path('app/' . $witnessModel->witness_signature_path);
                if (file_exists($fullPath)) {
                    $witnessModelPath = $fullPath;
                    $witnessModelDate = $witnessModel->witnessed_at?->format('d M Y, h:i A') ?? '';
                    $hasWitness = true;
                }
            }
        }

        if ($hasTenant || $hasManager || $hasWitness || $hasAdvocate) {
            $sigBlock .= '<div style="margin-top:24px;padding:12px 0;border-top:2px solid #c8a020;">';
            $sigBlock .= '<table style="width:100%;border-collapse:collapse;">';

            // ── Row 1: Tenant + Manager ──
            if ($hasTenant || $hasManager) {
                $sigBlock .= '<tr>';

                // Tenant cell
                $sigBlock .= '<td style="width:50%;vertical-align:top;padding-right:12px;padding-bottom:12px;">';
                $sigBlock .= '<p style="font-size:9pt;color:#333;margin:0 0 4px 0;"><strong>TENANT / LESSEE SIGNATURE</strong></p>';
                if ($hasTenant) {
                    $tenantSignedAt = ($tenantSignature->signed_at ?? $tenantSignature->created_at)?->format('d M Y, h:i A') ?? '';
                    $tenantIp = htmlspecialchars($tenantSignature->ip_address ?? 'N/A');
                    $sigBlock .= '<img src="' . $tenantSigPath . '" alt="Tenant Signature" style="max-width:200px;max-height:70px;border-bottom:1px solid #000;display:block;margin-bottom:4px;">';
                    $sigBlock .= '<p style="font-size:8pt;color:#555;margin:0;">Date: ' . $tenantSignedAt . '<br>IP: ' . $tenantIp . '</p>';
                } else {
                    $sigBlock .= '<div style="border-bottom:1px solid #000;height:50px;margin-bottom:4px;"></div>';
                    $sigBlock .= '<p style="font-size:8pt;color:#aaa;margin:0;">Not yet signed</p>';
                }
                $sigBlock .= '</td>';

                // Manager cell
                $sigBlock .= '<td style="width:50%;vertical-align:top;padding-left:12px;padding-bottom:12px;border-left:1px solid #e0e0e0;">';
                $sigBlock .= '<p style="font-size:9pt;color:#333;margin:0 0 4px 0;"><strong>PROPERTY MANAGER SIGNATURE</strong></p>';
                if ($hasManager) {
                    $mgrSignedAt = ($managerSignature->signed_at ?? $managerSignature->created_at)?->format('d M Y, h:i A') ?? '';
                    $mgrName = htmlspecialchars($managerSignature->signed_by_name ?? 'Property Manager');
                    $mgrIp = htmlspecialchars($managerSignature->ip_address ?? 'N/A');
                    $sigBlock .= '<img src="' . $managerSigPath . '" alt="Manager Signature" style="max-width:200px;max-height:70px;border-bottom:1px solid #000;display:block;margin-bottom:4px;">';
                    $sigBlock .= '<p style="font-size:8pt;color:#555;margin:0;">' . $mgrName . '<br>Date: ' . $mgrSignedAt . '<br>IP: ' . $mgrIp . '</p>';
                } else {
                    $sigBlock .= '<div style="border-bottom:1px solid #000;height:50px;margin-bottom:4px;"></div>';
                    $sigBlock .= '<p style="font-size:8pt;color:#aaa;margin:0;">Pending countersignature</p>';
                }
                $sigBlock .= '</td>';

                $sigBlock .= '</tr>';
            }

            // ── Row 2: Witness + Advocate ──
            if ($hasWitness || $hasAdvocate) {
                $sigBlock .= '<tr style="border-top:1px dashed #ddd;">';

                // Witness cell
                $sigBlock .= '<td style="width:50%;vertical-align:top;padding-right:12px;padding-top:8px;">';
                $sigBlock .= '<p style="font-size:9pt;color:#333;margin:0 0 4px 0;"><strong>WITNESS SIGNATURE</strong></p>';
                if ($hasWitness) {
                    $witnessImgPath = $witnessSigPath ?? $witnessModelPath;
                    if ($witnessSignature) {
                        $witnessDate = ($witnessSignature->signed_at ?? $witnessSignature->created_at)?->format('d M Y, h:i A') ?? '';
                    } else {
                        $witnessDate = $witnessModelDate ?? '';
                    }
                    $witnessModel2 = $lease->witnesses->where('witnessed_party', 'tenant')->sortByDesc('witnessed_at')->first();
                    $witnessName = htmlspecialchars($witnessModel2?->witnessed_by_name ?? '');
                    $witnessId   = htmlspecialchars($witnessModel2?->witness_id_number ?? '');
                    $sigBlock .= '<img src="' . $witnessImgPath . '" alt="Witness Signature" style="max-width:180px;max-height:60px;border-bottom:1px solid #000;display:block;margin-bottom:4px;">';
                    $sigBlock .= '<p style="font-size:8pt;color:#555;margin:0;">';
                    if ($witnessName) {
                        $sigBlock .= 'Name: ' . $witnessName . '<br>';
                    }
                    if ($witnessId) {
                        $sigBlock .= 'ID: ' . $witnessId . '<br>';
                    }
                    $sigBlock .= 'Date: ' . $witnessDate . '</p>';
                } else {
                    $sigBlock .= '<div style="border-bottom:1px solid #000;height:50px;margin-bottom:4px;"></div>';
                    $sigBlock .= '<p style="font-size:8pt;color:#aaa;margin:0;">No witness recorded</p>';
                }
                $sigBlock .= '</td>';

                // Advocate cell
                $sigBlock .= '<td style="width:50%;vertical-align:top;padding-left:12px;padding-top:8px;border-left:1px solid #e0e0e0;">';
                $sigBlock .= '<p style="font-size:9pt;color:#333;margin:0 0 4px 0;"><strong>ADVOCATE / COMMISSIONER FOR OATHS</strong></p>';
                if ($hasAdvocate) {
                    $advDate = ($advocateSignature->signed_at ?? $advocateSignature->created_at)?->format('d M Y, h:i A') ?? '';
                    $advName = htmlspecialchars($advocateSignature->signed_by_name ?? '');
                    $sigBlock .= '<img src="' . $advocateSigPath . '" alt="Advocate Signature" style="max-width:180px;max-height:60px;border-bottom:1px solid #000;display:block;margin-bottom:4px;">';
                    $sigBlock .= '<p style="font-size:8pt;color:#555;margin:0;">';
                    if ($advName) {
                        $sigBlock .= 'Name: ' . $advName . '<br>';
                    }
                    $sigBlock .= 'Date: ' . $advDate . '</p>';
                } else {
                    $sigBlock .= '<div style="border-bottom:1px solid #000;height:50px;margin-bottom:4px;"></div>';
                    $sigBlock .= '<p style="font-size:8pt;color:#aaa;margin:0;">Pending advocate stamp</p>';
                }
                $sigBlock .= '</td>';

                $sigBlock .= '</tr>';
            }

            $sigBlock .= '</table></div>';
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
            'start_date_year'  => $sd->format('y'),   // 2-digit year fits the small box on the form
            'end_date_day'     => $ed->format('d'),
            'end_date_month'   => $ed->format('m'),
            'end_date_year'    => $ed->format('Y'),
            // Lease term duration for "The Term" tiny boxes (years and months count)
            'lease_years'  => '1',
            'lease_months' => '0',
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
            'grant_of_lease_duration' => '5 year(s) 3 month(s)',
            'monthly_rent'   => $rent !== null ? number_format((float) $rent, 2) : '50,000.00',
            'deposit_amount' => $deposit !== null ? number_format((float) $deposit, 2) : '100,000.00',
            'vat_amount'     => $rent !== null ? number_format((float) $rent * 0.16, 2) : '8,000.00',
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
        $leaseYears  = '';
        $leaseMonths = '';
        if ($termMonths) {
            $years  = intdiv((int) $termMonths, 12);
            $months = (int) $termMonths % 12;
            $leaseYears  = (string) $years;
            $leaseMonths = (string) $months;
            if ($years && $months) {
                $durationLabel = "{$years} year(s) {$months} month(s)";
            } elseif ($years) {
                $durationLabel = "{$years} year(s)";
            } else {
                $durationLabel = "{$months} month(s)";
            }
        } elseif ($startDate && $endDate) {
            // Compute from date diff when term_months not set
            $diff = $startDate->diff($endDate);
            $leaseYears  = (string) $diff->y;
            $leaseMonths = (string) $diff->m;
        }

        return [
            // Date at top: "dated the __ day on the month of __ in the year __" (no slashes; document has its own separators)
            'lease_date_day'   => $startDate ? $startDate->format('d') : '',
            'lease_date_month' => $startDate ? $startDate->format('F') : '',
            'lease_date_year'  => $startDate ? $startDate->format('Y') : '',

            // Term "from __ / __ / __ To __ / __ / __" — separate day/month/year so slashes stay on the form
            // start_date_year uses 2-digit format ('y') to fit the small box printed on the commercial lease form
            'start_date_day'   => $startDate ? $startDate->format('d') : '',
            'start_date_month' => $startDate ? $startDate->format('m') : '',
            'start_date_year'  => $startDate ? $startDate->format('y') : '',
            'end_date_day'     => $endDate ? $endDate->format('d') : '',
            'end_date_month'   => $endDate ? $endDate->format('m') : '',
            'end_date_year'    => $endDate ? $endDate->format('Y') : '',

            // Lease term duration numbers for "The Term" section tiny boxes
            'lease_years'  => $leaseYears,
            'lease_months' => $leaseMonths,

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
            'grant_of_lease_duration' => $durationLabel,

            // Financials (number_format for clean display e.g. 279,270.00)
            'monthly_rent'     => $rent !== null ? number_format((float) $rent, 2) : '',
            'deposit_amount'   => $deposit !== null ? number_format((float) $deposit, 2) : '',
            'vat_amount'       => $vatAmount !== null ? number_format((float) $vatAmount, 2) : '',

            // Rent review (optional — set on lease when creating)
            'rent_review_years' => $lease->rent_review_years ? (string) $lease->rent_review_years : '',
            'rent_review_rate'  => $lease->rent_review_rate ? number_format((float) $lease->rent_review_rate, 1) : '',

            // Reference (for any reference fields in template)
            'reference_number' => $lease->reference_number ?? '',
        ];
    }

    /**
     * Collect text field values for the new-style 6-box signing page overlay.
     * Called by generate() to build the $textFields array passed to stampAllSigningFields().
     * Returns empty strings for missing data — stampAllSigningFields() skips empty values.
     *
     * @return array<string, string>
     */
    private function signingPageTextFields(Lease $lease): array
    {
        // ── Lessor (manager or landlord who countersigned) ────────────────────
        $managerSig  = $lease->digitalSignatures->where('signer_type', 'manager')->sortByDesc('created_at')->first();
        $lessorName  = $managerSig?->signed_by_name ?? $lease->landlord?->names ?? '';
        $lessorId    = '';
        if ($managerSig?->signed_by_user_id) {
            $lessorId = $lease->zoneManager?->national_id ?? '';
        }
        if ($lessorId === '') {
            $lessorId = $lease->landlord?->national_id ?? '';
        }
        $lessorDate = $managerSig?->signed_at?->format('d/m/Y') ?? '';

        // ── Lessor witness ────────────────────────────────────────────────────
        $lessorWitness = $lease->witnesses->where('witnessed_party', 'lessor')->sortByDesc('witnessed_at')->first();
        $lessorWitnessName = $lessorWitness?->witnessed_by_name ?? $lease->lessor_witness_name ?? '';
        $lessorWitnessId   = $lessorWitness?->witness_id_number ?? $lease->lessor_witness_id ?? '';
        $lessorWitnessDate = $lessorWitness?->witnessed_at?->format('d/m/Y') ?? '';

        // ── Lessor advocate ───────────────────────────────────────────────────
        $lessorTracking = null;
        if ($lease->relationLoaded('lawyerTrackings')) {
            $lessorTracking = $lease->lawyerTrackings->where('side', 'lessor')->first();
        }
        $lessorAdvName = $lessorTracking?->advocate_name ?? $lessorTracking?->lawyer?->name ?? $lease->lessor_advocate_name ?? '';
        $lessorAdvFirm = $lessorTracking?->advocate_firm ?? $lessorTracking?->lawyer?->firm ?? '';
        $lessorAdvLsk  = $lessorTracking?->advocate_lsk_number ?? $lessorTracking?->lawyer?->lsk_number ?? '';
        $lessorAdvSig  = $lease->digitalSignatures->where('signer_type', 'lessor_advocate')->sortByDesc('created_at')->first();
        $lessorAdvDate = $lessorAdvSig?->signed_at?->format('d/m/Y') ?? '';

        // ── Lessee (tenant) ───────────────────────────────────────────────────
        $tenantSig  = $lease->digitalSignatures->where('signer_type', 'tenant')->sortByDesc('created_at')->first();
        $lesseeName = $lease->tenant?->names ?? '';
        $lesseeId   = $lease->tenant?->national_id ?? $lease->tenant?->passport_number ?? '';
        $lesseeDate = $tenantSig?->signed_at?->format('d/m/Y') ?? '';

        // ── Lessee witness ────────────────────────────────────────────────────
        $lesseeWitness = $lease->witnesses->where('witnessed_party', 'tenant')->sortByDesc('witnessed_at')->first();
        $lesseeWitnessName = $lesseeWitness?->witnessed_by_name ?? '';
        $lesseeWitnessId   = $lesseeWitness?->witness_id_number ?? '';
        $lesseeWitnessDate = $lesseeWitness?->witnessed_at?->format('d/m/Y') ?? '';

        // ── Lessee advocate ───────────────────────────────────────────────────
        $lesseeTracking = null;
        if ($lease->relationLoaded('lawyerTrackings')) {
            $lesseeTracking = $lease->lawyerTrackings->where('side', 'lessee')->first();
        }
        $lesseeAdvName = $lesseeTracking?->advocate_name ?? $lesseeTracking?->lawyer?->name ?? '';
        $lesseeAdvFirm = $lesseeTracking?->advocate_firm ?? $lesseeTracking?->lawyer?->firm ?? '';
        $lesseeAdvLsk  = $lesseeTracking?->advocate_lsk_number ?? $lesseeTracking?->lawyer?->lsk_number ?? '';
        $lesseeAdvSig  = $lease->digitalSignatures->whereIn('signer_type', ['lessee_advocate', 'advocate'])->sortByDesc('created_at')->first();
        $lesseeAdvDate = $lesseeAdvSig?->signed_at?->format('d/m/Y') ?? '';

        return [
            // Box 1 — Lessor
            'lessor_sig_name' => $lessorName,
            'lessor_sig_id'   => $lessorId,
            'lessor_sig_date' => $lessorDate,
            // Box 2 — Lessor Witness
            'lessor_witness_name' => $lessorWitnessName,
            'lessor_witness_id'   => $lessorWitnessId,
            'lessor_witness_date' => $lessorWitnessDate,
            // Box 3 — Lessor Advocate
            'lessor_advocate_name' => $lessorAdvName,
            'lessor_advocate_firm' => $lessorAdvFirm,
            'lessor_advocate_lsk'  => $lessorAdvLsk,
            'lessor_advocate_date' => $lessorAdvDate,
            // Box 4 — Lessee
            'lessee_sig_name' => $lesseeName,
            'lessee_sig_id'   => $lesseeId,
            'lessee_sig_date' => $lesseeDate,
            // Box 5 — Lessee Witness
            'lessee_witness_name' => $lesseeWitnessName,
            'lessee_witness_id'   => $lesseeWitnessId,
            'lessee_witness_date' => $lesseeWitnessDate,
            // Box 6 — Lessee Advocate
            'lessee_advocate_name' => $lesseeAdvName,
            'lessee_advocate_firm' => $lesseeAdvFirm,
            'lessee_advocate_lsk'  => $lesseeAdvLsk,
            'lessee_advocate_date' => $lesseeAdvDate,
        ];
    }

    /**
     * Filter a coordinate map to only new-style signing-page keys
     * (lessor_*, lessee_* prefixed keys introduced for the 6-box layout).
     * Returns empty array if no new-style keys are present, which causes
     * stampAllSigningFields() to be skipped (backward compat for old templates).
     *
     * @param  array<string, mixed>  $coordinates
     * @return array<string, mixed>
     */
    private function filterNewStyleSigningCoords(array $coordinates): array
    {
        return array_filter(
            $coordinates,
            static fn ($key) => str_starts_with($key, 'lessor_') || str_starts_with($key, 'lessee_'),
            ARRAY_FILTER_USE_KEY,
        );
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

    /**
     * Build date text entries for all available signatures.
     * Each entry is placed just below the corresponding signature's coordinate position.
     *
     * @return array<array{text: string, page: int, x: float, y: float}>
     */
    private function buildSignatureDateEntries(
        Lease $lease,
        array $coordinates,
        ?\App\Models\DigitalSignature $tenantSig,
        ?\App\Models\DigitalSignature $managerSig,
        ?\App\Models\DigitalSignature $witnessSig,
        ?\App\Models\DigitalSignature $advocateSig,
    ): array {
        $entries = [];

        // Tenant date
        if ($tenantSig) {
            $coord = $coordinates['tenant_signature'] ?? ['page' => 2, 'x' => 140, 'y' => 240, 'height' => 30];
            $entries[] = [
                'text' => 'Date: ' . ($tenantSig->signed_at ?? $tenantSig->created_at)?->format('d/m/Y'),
                'page' => (int) ($coord['page'] ?? 2),
                'x'    => (float) ($coord['x'] ?? 140),
                'y'    => (float) ($coord['y'] ?? 240) + (float) ($coord['height'] ?? 30) + 2,
            ];
        }

        // Manager date
        if ($managerSig) {
            $coord = $coordinates['manager_signature'] ?? ['page' => 2, 'x' => 140, 'y' => 280, 'height' => 30];
            $sigY = (float) ($coord['y'] ?? 280);
            $anchor = (string) ($coord['anchor'] ?? 'above');
            $height = (float) ($coord['height'] ?? 30);
            // When anchor is 'above', the image is placed above the y — date goes at y + 2
            $dateY = ($anchor === 'above') ? $sigY + 2 : $sigY + $height + 2;
            $entries[] = [
                'text' => 'Date: ' . ($managerSig->signed_at ?? $managerSig->created_at)?->format('d/m/Y'),
                'page' => (int) ($coord['page'] ?? 2),
                'x'    => (float) ($coord['x'] ?? 140),
                'y'    => $dateY,
            ];
        }

        // Witness date — from DigitalSignature or LeaseWitness
        $witnessDate = null;
        if ($witnessSig) {
            $witnessDate = ($witnessSig->signed_at ?? $witnessSig->created_at)?->format('d/m/Y');
        } else {
            $witnessModel = $lease->witnesses
                ->where('witnessed_party', 'tenant')
                ->sortByDesc('witnessed_at')
                ->first();
            if ($witnessModel?->witnessed_at) {
                $witnessDate = $witnessModel->witnessed_at->format('d/m/Y');
            }
        }
        if ($witnessDate) {
            $coord = $coordinates['witness_signature'] ?? ['page' => 2, 'x' => 20, 'y' => 260, 'height' => 20];
            $entries[] = [
                'text' => 'Date: ' . $witnessDate,
                'page' => (int) ($coord['page'] ?? 2),
                'x'    => (float) ($coord['x'] ?? 20),
                'y'    => (float) ($coord['y'] ?? 260) + (float) ($coord['height'] ?? 20) + 2,
            ];
        }

        // Advocate date
        if ($advocateSig) {
            $coord = $coordinates['advocate_signature'] ?? ['page' => 2, 'x' => 20, 'y' => 280, 'height' => 18];
            $entries[] = [
                'text' => 'Date: ' . ($advocateSig->signed_at ?? $advocateSig->created_at)?->format('d/m/Y'),
                'page' => (int) ($coord['page'] ?? 2),
                'x'    => (float) ($coord['x'] ?? 20),
                'y'    => (float) ($coord['y'] ?? 280) + (float) ($coord['height'] ?? 18) + 2,
            ];
        }

        return $entries;
    }
}
