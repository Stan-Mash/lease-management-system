<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\LeaseTemplate;
use App\Models\LeaseTemplateVersion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * LeaseTemplateManagementService
 * 
 * Manages template versioning, change tracking, and audit logging
 * Ensures immutability of template versions and comprehensive audit trails
 */
class LeaseTemplateManagementService
{
    /**
     * Create a new template with initial version
     */
    public function createTemplate(array $data, string $changeSummary = 'Initial template creation'): LeaseTemplate
    {
        $data['created_by'] = auth()->id();
        $data['version_number'] = 1;

        $template = LeaseTemplate::create($data);

        // Create initial version snapshot
        $this->createVersion($template, $changeSummary);

        Log::info('Template created', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_type' => $template->template_type,
            'created_by' => auth()->id(),
        ]);

        return $template->fresh();
    }

    /**
     * Update template and create version snapshot
     */
    public function updateTemplate(LeaseTemplate $template, array $data, string $changeSummary): LeaseTemplate
    {
        // Determine what changed
        $changes = $this->identifyChanges($template, $data);

        // Save changes to template
        $data['updated_by'] = auth()->id();
        $template->update($data);

        // Create version snapshot
        $version = $this->createVersion($template, $changeSummary, $changes);

        Log::info('Template updated and versioned', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'version_number' => $version->version_number,
            'change_summary' => $changeSummary,
            'changes' => $changes,
            'updated_by' => auth()->id(),
        ]);

        return $template->fresh();
    }

    /**
     * Create a version snapshot of the current template state
     */
    public function createVersion(
        LeaseTemplate $template,
        string $changeSummary = null,
        ?array $changes = null
    ): LeaseTemplateVersion {
        $newVersionNumber = $template->versions()->max('version_number') + 1;

        return $template->versions()->create([
            'version_number' => $newVersionNumber,
            'blade_content' => $template->blade_content,
            'css_styles' => $template->css_styles,
            'layout_config' => $template->layout_config,
            'branding_config' => $template->branding_config,
            'available_variables' => $template->available_variables,
            'created_by' => auth()->id(),
            'change_summary' => $changeSummary,
            'changes_diff' => $changes,
        ]);
    }

    /**
     * Identify what changed between old and new data
     */
    private function identifyChanges(LeaseTemplate $template, array $newData): array
    {
        $changes = [];

        foreach ($newData as $key => $newValue) {
            if (!in_array($key, ['created_by', 'updated_by'])) {
                $oldValue = $template->getAttribute($key);

                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
                        'new' => is_array($newValue) ? json_encode($newValue) : $newValue,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Restore template to a specific version
     */
    public function restoreToVersion(LeaseTemplate $template, int $versionNumber, string $reason = 'Restored from previous version'): bool
    {
        $version = $template->versions()
            ->where('version_number', $versionNumber)
            ->first();

        if (!$version) {
            Log::warning('Attempted to restore non-existent version', [
                'template_id' => $template->id,
                'version_number' => $versionNumber,
            ]);
            return false;
        }

        // Update template
        $template->update([
            'blade_content' => $version->blade_content,
            'css_styles' => $version->css_styles,
            'layout_config' => $version->layout_config,
            'branding_config' => $version->branding_config,
            'available_variables' => $version->available_variables,
            'updated_by' => auth()->id(),
        ]);

        // Create new version with restoration note
        $this->createVersion(
            $template,
            "{$reason} (from version {$versionNumber})",
            [
                'restored_from_version' => $versionNumber,
                'reason' => $reason,
            ]
        );

        Log::info('Template restored to previous version', [
            'template_id' => $template->id,
            'restored_to_version' => $versionNumber,
            'created_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Get version history with change details
     */
    public function getVersionHistory(LeaseTemplate $template): array
    {
        return $template->versions()
            ->with('creator:id,name,email')
            ->orderByDesc('version_number')
            ->get()
            ->map(function (LeaseTemplateVersion $version) {
                return [
                    'version_number' => $version->version_number,
                    'created_at' => $version->created_at,
                    'created_by' => $version->creator?->name ?? 'System',
                    'change_summary' => $version->change_summary,
                    'changes' => $version->changes_diff,
                    'number_of_variables' => count($version->available_variables ?? []),
                ];
            })
            ->toArray();
    }

    /**
     * Get templates of a specific type with their active version
     */
    public function getTemplatesForType(string $templateType, bool $activeOnly = true)
    {
        $query = LeaseTemplate::forType($templateType);

        if ($activeOnly) {
            $query->active();
        }

        return $query->with(['versions' => function ($q) {
            $q->orderByDesc('version_number')->limit(1);
        }, 'creator:id,name'])
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get the active version of a template
     */
    public function getActiveVersion(LeaseTemplate $template): LeaseTemplateVersion
    {
        return $template->versions()
            ->orderByDesc('version_number')
            ->firstOrFail();
    }

    /**
     * Compare two versions side by side
     */
    public function compareVersions(LeaseTemplate $template, int $version1, int $version2): array
    {
        $v1 = $template->versions()->where('version_number', $version1)->firstOrFail();
        $v2 = $template->versions()->where('version_number', $version2)->firstOrFail();

        return [
            'version_1' => [
                'version_number' => $v1->version_number,
                'created_at' => $v1->created_at,
                'change_summary' => $v1->change_summary,
                'content_hash' => hash('sha256', $v1->blade_content),
            ],
            'version_2' => [
                'version_number' => $v2->version_number,
                'created_at' => $v2->created_at,
                'change_summary' => $v2->change_summary,
                'content_hash' => hash('sha256', $v2->blade_content),
            ],
            'differences' => $this->calculateDiff($v1->blade_content, $v2->blade_content),
        ];
    }

    /**
     * Calculate differences between two content strings
     */
    private function calculateDiff(string $old, string $new): array
    {
        $oldLines = explode("\n", $old);
        $newLines = explode("\n", $new);

        $added = array_diff($newLines, $oldLines);
        $removed = array_diff($oldLines, $newLines);

        return [
            'lines_added' => count($added),
            'lines_removed' => count($removed),
            'total_changes' => count($added) + count($removed),
        ];
    }

    /**
     * Validate template before using in lease
     */
    public function validateTemplate(LeaseTemplate $template): array
    {
        $errors = [];

        // Check if template has content
        if (empty($template->blade_content)) {
            $errors[] = 'Template has no content';
        }

        // Check required variables
        if ($template->required_variables) {
            $available = $template->extractVariables();
            $required = $template->required_variables;

            foreach ($required as $var) {
                if (!in_array($var, $available)) {
                    $errors[] = "Required variable \${$var} not found in template";
                }
            }
        }

        // Check template type is valid
        $validTypes = ['residential_major', 'residential_micro', 'commercial'];
        if (!in_array($template->template_type, $validTypes)) {
            $errors[] = "Invalid template type: {$template->template_type}";
        }

        return $errors;
    }

    /**
     * Get templates used by active leases
     */
    public function getTemplateUsageStats(LeaseTemplate $template): array
    {
        $totalLeases = Lease::where('lease_template_id', $template->id)->count();
        $activeLeases = Lease::where('lease_template_id', $template->id)
            ->whereIn('workflow_state', ['active', 'pending_landlord_approval', 'pending_tenant_signature'])
            ->count();

        $versionUsage = [];
        foreach ($template->versions as $version) {
            $count = Lease::where('lease_template_id', $template->id)
                ->where('template_version_used', $version->version_number)
                ->count();

            if ($count > 0) {
                $versionUsage[] = [
                    'version_number' => $version->version_number,
                    'lease_count' => $count,
                    'created_at' => $version->created_at,
                ];
            }
        }

        return [
            'total_leases' => $totalLeases,
            'active_leases' => $activeLeases,
            'version_usage' => $versionUsage,
            'latest_version' => $template->version_number,
        ];
    }

    /**
     * Export template version as PDF reference
     */
    public function exportVersionReference(LeaseTemplateVersion $version, string $format = 'html'): string
    {
        if ($format === 'html') {
            return view('pdf.template-reference', [
                'version' => $version,
                'template' => $version->template,
            ])->render();
        }

        return $version->blade_content;
    }

    /**
     * Archive old versions (keep last N versions)
     */
    public function archiveOldVersions(LeaseTemplate $template, int $keepVersions = 10): int
    {
        $versionsToKeep = $template->versions()
            ->orderByDesc('version_number')
            ->limit($keepVersions)
            ->pluck('version_number')
            ->toArray();

        $archivedCount = $template->versions()
            ->whereNotIn('version_number', $versionsToKeep)
            ->delete();

        Log::info('Old template versions archived', [
            'template_id' => $template->id,
            'archived_count' => $archivedCount,
            'kept_versions' => $keepVersions,
        ]);

        return $archivedCount;
    }
}
