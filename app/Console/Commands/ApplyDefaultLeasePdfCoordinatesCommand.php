<?php

namespace App\Console\Commands;

use App\Models\LeaseTemplate;
use App\Services\DefaultLeasePdfCoordinateMap;
use Illuminate\Console\Command;

/**
 * Apply the default "Particulars" coordinate map to lease templates that have
 * a source PDF but no (or empty) pdf_coordinate_map. Use so generated leases
 * align text in the document boxes (font 12, width/align).
 */
class ApplyDefaultLeasePdfCoordinatesCommand extends Command
{
    protected $signature = 'templates:apply-default-coordinates
                            {--type= : Template type: commercial, residential_major, residential_micro, or all (default: all with PDF and no map)}
                            {--force : Overwrite existing coordinate map}';

    protected $description = 'Apply default PDF coordinate map to templates that have a PDF but no map';

    public function handle(): int
    {
        $type = $this->option('type');
        $force = $this->option('force');

        $query = LeaseTemplate::query()
            ->whereNotNull('source_pdf_path')
            ->where('source_pdf_path', '!=', '');

        $types = ['commercial', 'residential_major', 'residential_micro'];
        if ($type !== null && $type !== '') {
            if (! in_array($type, $types, true)) {
                $this->error("--type must be one of: commercial, residential_major, residential_micro, or omit for all.");
                return self::FAILURE;
            }
            $query->where('template_type', $type);
        }

        $templates = $query->get();

        if (! $force) {
            $templates = $templates->filter(fn (LeaseTemplate $t) => empty($t->pdf_coordinate_map) || $t->pdf_coordinate_map === []);
        }
        if ($templates->isEmpty()) {
            $this->info('No templates found with a source PDF and ' . ($force ? 'any map' : 'no coordinate map') . '.');
            if (! $force) {
                $this->line('Use --force to overwrite existing coordinate maps.');
            }
            return self::SUCCESS;
        }

        // Page layout per template type:
        //   commercial:        cover page (p1) + particulars (p2) + rent review (p3) + signing (p7)
        //   residential_major: particulars (p1) + rent review (p1) + signing (p5)
        //   residential_micro: particulars (p1) + rent review (p1) + signing (p2)
        $pageLayout = [
            'commercial'        => ['particulars' => 2, 'rentReview' => 3, 'signing' => 7],
            'residential_major' => ['particulars' => 1, 'rentReview' => 1, 'signing' => 5],
            'residential_micro' => ['particulars' => 1, 'rentReview' => 1, 'signing' => 2],
        ];

        foreach ($templates as $template) {
            $layout = $pageLayout[$template->template_type] ?? ['particulars' => 1, 'rentReview' => 1, 'signing' => 2];
            $map = array_merge(
                DefaultLeasePdfCoordinateMap::page1Fields($layout['particulars'], $layout['rentReview']),
                DefaultLeasePdfCoordinateMap::legacySignaturePlaceholders($layout['signing']),
                DefaultLeasePdfCoordinateMap::signingPage($layout['signing']),
            );
            $template->update(['pdf_coordinate_map' => $map]);
            $this->line("Applied default map (particulars p{$layout['particulars']}, signing p{$layout['signing']}) to: {$template->name} ({$template->slug}, type: {$template->template_type})");
        }

        $this->info('Done. You can fine-tune positions via Edit Template → Pick positions or edit the JSON in PDF Upload.');
        return self::SUCCESS;
    }
}
