<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\LeaseTemplate;
use Exception;
use Illuminate\Support\Facades\Blade;

class TemplateRenderService
{
    /**
     * Render a lease template with the lease data.
     *
     * @param  LeaseTemplate  $template
     * @param  Lease  $lease
     * @return string
     *
     * @throws Exception
     */
    public function render(LeaseTemplate $template, Lease $lease): string
    {
        // 1. Get the content - try versions first, then fall back to template's blade_content
        $templateContent = null;

        // Try to get from version first
        $version = $template->versions()->latest('version_number')->first();

        if ($version) {
            $templateContent = $version->blade_content;
        }

        // Fall back to template's own blade_content
        if (! $templateContent && $template->blade_content) {
            $templateContent = $template->blade_content;
        }

        if (! $templateContent) {
            // Fallback to prevent 500 Error when template data is missing
            return "
            <div style='font-family: sans-serif; text-align: center; padding: 50px; border: 2px solid red;'>
                <h1 style='color: red;'>Template Content Missing</h1>
                <p>The template <strong>'{$template->name}'</strong> has no content in the database.</p>
                <hr>
                <p style='text-align: left;'><strong>To fix this:</strong></p>
                <ol style='text-align: left;'>
                    <li>Ensure the file <code>database/seeders/ExactLeaseTemplateSeeder.php</code> exists.</li>
                    <li>Run: <code>composer dump-autoload</code></li>
                    <li>Run: <code>php artisan db:seed --class=ExactLeaseTemplateSeeder</code></li>
                </ol>
            </div>";
        }

        // 2. Prepare the data variables
        $data = [
            'lease' => $lease,
            'tenant' => $lease->tenant,
            'unit' => $lease->unit,
            'property' => $lease->property,
            'landlord' => $lease->landlord ?? $lease->property->landlord ?? null,
        ];

        // 3. Render the HTML string using Laravel's Blade engine
        try {
            return Blade::render($templateContent, $data);
        } catch (Exception $e) {
            throw new Exception("Error rendering template '{$template->name}': ".$e->getMessage());
        }
    }
}
