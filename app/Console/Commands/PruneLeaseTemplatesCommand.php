<?php

namespace App\Console\Commands;

use App\Models\Lease;
use App\Models\LeaseTemplate;
use Illuminate\Console\Command;

/**
 * Keep only the 3 default lease templates (one per type: residential_major, residential_micro, commercial)
 * and soft-delete the rest. Reassigns any leases that used deleted templates to the
 * default template of the same type.
 */
class PruneLeaseTemplatesCommand extends Command
{
    protected $signature = 'lease-templates:prune-to-three
                            {--dry-run : Show what would be done without deleting}
                            {--force : Skip confirmation}';

    protected $description = 'Keep only 3 lease templates (one default per type) and remove the rest';

    private const TYPES = ['residential_major', 'residential_micro', 'commercial'];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $keep = collect(self::TYPES)
            ->map(fn (string $type) => LeaseTemplate::where('template_type', $type)->where('is_default', true)->first())
            ->filter()
            ->values();

        if ($keep->count() < 3) {
            $this->warn('Need exactly one default template per type (residential_major, residential_micro, commercial). Found: ' . $keep->count());
            $this->line('Add PDFs to storage/app/templates/leases and run: php artisan templates:use-pdf-only');
            $this->line('Then run this command again.');
            return self::FAILURE;
        }

        $keepIds = $keep->pluck('id')->all();
        $toDelete = LeaseTemplate::whereNotIn('id', $keepIds)->get();
        if ($toDelete->isEmpty()) {
            $this->info('Already only 3 templates. Nothing to prune.');
            return self::SUCCESS;
        }

        $this->table(
            ['Slug', 'Name', 'Type'],
            $toDelete->map(fn ($t) => [$t->slug, $t->name, $t->template_type])
        );
        $this->newLine();
        $this->warn('The above ' . $toDelete->count() . ' template(s) will be removed. Leases using them will be reassigned to the kept template of the same type.');

        if (! $force && ! $dryRun && ! $this->confirm('Continue?')) {
            return self::SUCCESS;
        }

        $byType = $keep->keyBy('template_type');

        foreach ($toDelete as $template) {
            $replacement = $byType->get($template->template_type) ?? $keep->first();
            $reassigned = Lease::where('lease_template_id', $template->id)->update(['lease_template_id' => $replacement->id]);
            if ($reassigned > 0) {
                $this->line("Reassigned {$reassigned} lease(s) from «{$template->name}» to «{$replacement->name}».");
            }
            if (! $dryRun) {
                $template->delete();
                $this->info("Removed: {$template->slug}");
            } else {
                $this->line("[dry-run] Would remove: {$template->slug}");
            }
        }

        if ($dryRun) {
            $this->info('Dry run. Run without --dry-run to apply.');
        } else {
            $this->info('Done. Only 3 templates remain.');
        }

        return self::SUCCESS;
    }
}
