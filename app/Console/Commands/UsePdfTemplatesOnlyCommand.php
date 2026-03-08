<?php

namespace App\Console\Commands;

use App\Models\LeaseTemplate;
use App\Models\LeaseTemplateAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Makes PDFs in storage/app/templates/leases the sole source of lease templates.
 * Removes all existing templates and recreates only from PDFs in that folder.
 * Each imported template is set as the default for its type.
 */
class UsePdfTemplatesOnlyCommand extends Command
{
    protected $signature = 'templates:use-pdf-only
                            {--path= : Override path to PDF folder (default: storage/app/templates/leases)}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Remove all lease templates and use only PDFs from storage/app/templates/leases as defaults';

    public function handle(): int
    {
        $path = $this->option('path') ?? storage_path('app/templates/leases');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
            $this->newLine();
        }

        if (! is_dir($path)) {
            $this->error("Directory not found: {$path}");
            $this->line('Create it and add your lease PDFs, then run this command again.');

            return self::FAILURE;
        }

        $pdfFiles = File::glob($path . '/*.pdf');
        if (empty($pdfFiles)) {
            $this->error('No PDF files found in: ' . $path);
            $this->line('Add at least one lease PDF (e.g. commercial, residential major/micro) and run again.');

            return self::FAILURE;
        }

        $this->info('PDFs to use as templates:');
        foreach ($pdfFiles as $p) {
            $this->line('  • ' . basename($p));
        }
        $this->newLine();

        $existingCount = LeaseTemplate::withTrashed()->count();
        if ($existingCount > 0) {
            $this->warn("Existing templates (including trashed): {$existingCount}. They will be removed.");
        }

        if (! $dryRun && $existingCount > 0 && ! $this->confirm('Proceed? This will permanently remove all current templates and replace them with ones from the PDF folder.')) {
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('[dry-run] Would remove all templates and import ' . count($pdfFiles) . ' from PDFs.');
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            // 1. Delete assignments (restrictOnDelete would block template deletion)
            $assignmentsDeleted = LeaseTemplateAssignment::query()->delete();
            if ($assignmentsDeleted > 0) {
                $this->line("Removed {$assignmentsDeleted} lease template assignment(s).");
            }

            // 2. Force-delete all templates (versions cascade)
            $deleted = LeaseTemplate::withTrashed()->forceDelete();
            $this->info("Removed {$deleted} lease template(s) and their versions.");

            // 3. Import from PDF folder via the existing import command
            $this->newLine();
            $this->info('Importing templates from PDFs...');
            $exitCode = $this->call('templates:import', [
                '--path' => $path,
                '--force' => true,
            ]);
            if ($exitCode !== 0) {
                DB::rollBack();
                $this->error('Import failed. Transaction rolled back.');

                return self::FAILURE;
            }

            // 4. Ensure each template type has exactly one default
            $templates = LeaseTemplate::all();
            foreach ($templates as $template) {
                LeaseTemplate::where('template_type', $template->template_type)
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
                $template->update(['is_default' => true, 'is_active' => true]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Done. Lease templates now come only from: ' . $path);
        $this->table(
            ['Name', 'Type', 'Default'],
            LeaseTemplate::all()->map(fn ($t) => [$t->name, $t->template_type, $t->is_default ? 'Yes' : ''])
        );

        return self::SUCCESS;
    }
}
