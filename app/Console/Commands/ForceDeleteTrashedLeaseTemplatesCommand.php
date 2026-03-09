<?php

namespace App\Console\Commands;

use App\Models\Lease;
use App\Models\LeaseTemplate;
use Illuminate\Console\Command;

/**
 * Permanently delete all trashed lease templates so only the current (non-trashed) ones remain.
 * Reassigns any leases still pointing at trashed templates to the default template of the same type.
 */
class ForceDeleteTrashedLeaseTemplatesCommand extends Command
{
    protected $signature = 'lease-templates:force-delete-trashed
                            {--dry-run : Show what would be done without deleting}
                            {--force : Skip confirmation}';

    protected $description = 'Permanently remove all trashed lease templates; keep only current ones';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $trashed = LeaseTemplate::onlyTrashed()->get();

        if ($trashed->isEmpty()) {
            $this->info('No trashed lease templates. Nothing to do.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Slug', 'Name', 'Type'],
            $trashed->map(fn ($t) => [$t->id, $t->slug, $t->name, $t->template_type])
        );
        $this->newLine();
        $this->warn($trashed->count() . ' trashed template(s) will be permanently deleted.');

        $trashedIds = $trashed->pluck('id')->all();
        $leasesUsing = Lease::whereIn('lease_template_id', $trashedIds)->count();
        if ($leasesUsing > 0) {
            $defaults = LeaseTemplate::whereIn('template_type', $trashed->pluck('template_type')->unique())
                ->where('is_default', true)
                ->get()
                ->keyBy('template_type');
            if ($defaults->isNotEmpty()) {
                $this->line("{$leasesUsing} lease(s) using these templates will be reassigned to the default template of the same type.");
            } else {
                $this->warn("{$leasesUsing} lease(s) reference these templates; some may be set to null if no default exists for that type.");
            }
        }

        if (! $force && ! $dryRun && ! $this->confirm('Continue?')) {
            return self::SUCCESS;
        }

        $defaults = LeaseTemplate::whereIn('template_type', $trashed->pluck('template_type')->unique())
            ->where('is_default', true)
            ->get()
            ->keyBy('template_type');
        $fallback = LeaseTemplate::where('is_default', true)->first();

        foreach ($trashed as $template) {
            $replacement = $defaults->get($template->template_type) ?? $fallback;
            if ($replacement) {
                $reassigned = Lease::where('lease_template_id', $template->id)->update([
                    'lease_template_id' => $replacement->id,
                    'template_version_used' => $replacement->version_number,
                ]);
                if ($reassigned > 0 && ! $dryRun) {
                    $this->line("Reassigned {$reassigned} lease(s) from trashed «{$template->name}» to «{$replacement->name}».");
                }
            }
            if (! $dryRun) {
                $template->forceDelete();
                $this->info("Deleted: {$template->slug}");
            } else {
                $this->line("[dry-run] Would permanently delete: {$template->slug}");
            }
        }

        if ($dryRun) {
            $this->info('Dry run. Run without --dry-run to apply.');
        } else {
            $this->info('Done. Only non-trashed templates remain.');
        }

        return self::SUCCESS;
    }
}
