# DEVELOPER QUICK REFERENCE

**For:** Developers integrating template versioning system  
**Purpose:** Quick API reference for common operations

---

## SERVICE INJECTION

```php
// In any controller/job/command
use App\Services\LeaseTemplateManagementService;
use App\Services\TemplateRenderServiceV2;

public function __construct(
    private LeaseTemplateManagementService $templateService,
    private TemplateRenderServiceV2 $renderService
) {}

// Or use app() helper
$service = app(LeaseTemplateManagementService::class);
```

---

## CREATING TEMPLATES

### Basic Creation
```php
$template = $this->templateService->createTemplate(
    [
        'name' => 'Residential Major',
        'template_type' => 'residential_major',
        'blade_content' => '<p>Lease content here</p>',
        'css_styles' => 'body { font-family: Arial; }',
        'layout_config' => json_encode([...]),
        'branding_config' => json_encode([...]),
        'is_active' => true,
        'is_default' => true,
    ],
    'Initial template from PDF'
);
// Returns: LeaseTemplate instance
// Auto-creates: LeaseTemplateVersion v1
```

---

## UPDATING TEMPLATES

### Simple Update
```php
$template = LeaseTemplate::find(1);

$this->templateService->updateTemplate(
    $template,
    [
        'blade_content' => '<p>Updated content</p>',
        'css_styles' => 'body { color: red; }',
    ],
    'Updated footer styling' // change summary
);
// Returns: Updated LeaseTemplate
// Auto-creates: New version (v2)
```

### Update Specific Fields
```php
// Only update content
$template->update(['blade_content' => $newContent]);

// This triggers model event:
// - Detects changes
// - Creates new version automatically
// - Records change summary
```

---

## RENDERING LEASES

### Render Current Version
```php
$lease = Lease::find(1);

// Auto-selects active template version
$html = $this->renderService->renderLease($lease);

// Returns: Rendered HTML string
// Automatically:
// - Loads template
// - Gets latest version
// - Records version in lease
// - Validates before render
```

### Render Specific Version
```php
$lease = Lease::find(1);
$version = LeaseTemplateVersion::where('version_number', 2)->first();

$html = $this->renderService->renderVersion($version, $lease);

// Returns: HTML with specific version
// Use case: Regenerate historical lease
```

### Get Preview
```php
$template = LeaseTemplate::find(1);

$html = $this->renderService->getTemplatePreview($template);

// Returns: HTML preview with sample data
// Use case: Admin dashboard preview
```

---

## VERSION MANAGEMENT

### Get Version History
```php
$template = LeaseTemplate::find(1);

$history = $this->templateService->getVersionHistory($template);

// Returns: Collection of versions
// Each version includes:
// - version_number
// - blade_content
// - change_summary
// - created_by (user id)
// - created_at (timestamp)
// - changes_diff (what changed)
```

### Restore Version
```php
$template = LeaseTemplate::find(1);

$this->templateService->restoreToVersion(
    $template,
    1, // version number to restore
    'Restoring to original version' // reason
);

// Returns: Updated LeaseTemplate
// Creates: New version with restored content
// History: All versions preserved
```

### Compare Versions
```php
$template = LeaseTemplate::find(1);

$diff = $this->templateService->compareVersions(
    $template,
    1, // from version
    2  // to version
);

// Returns: Array of differences
// Shows: What added/removed/changed
```

---

## VALIDATION

### Before Rendering
```php
$template = LeaseTemplate::find(1);
$lease = Lease::find(1);

// Manual validation
$isValid = $this->renderService->validateBeforeRender(
    $template,
    $lease
);

if (!$isValid) {
    throw new TemplateValidationException('...');
}

// Checks:
// - Template is active
// - Template type matches lease
// - All variables exist
```

### Template Validation
```php
$template = LeaseTemplate::find(1);

$isValid = $this->templateService->validateTemplate($template);

// Checks:
// - Template has content
// - Required variables exist
// - Valid Blade syntax
```

---

## USAGE STATISTICS

### Get Template Usage
```php
$template = LeaseTemplate::find(1);

$stats = $this->templateService->getTemplateUsageStats($template);

// Returns:
// [
//     'total_leases' => 150,
//     'versions' => [
//         '1' => 50,
//         '2' => 100,
//     ],
//     'last_used' => '2026-01-19 15:30:00',
//     'created_by' => 'John Doe',
// ]
```

---

## MODELS & RELATIONSHIPS

### LeaseTemplate
```php
$template = LeaseTemplate::find(1);

// Relationships
$versions = $template->versions; // All versions
$leases = $template->leases; // Leases using template
$creator = $template->creator; // User who created
$updater = $template->updater; // User who updated

// Scopes
$active = LeaseTemplate::active()->get();
$defaults = LeaseTemplate::default()->get();
$type = LeaseTemplate::forType('residential_major')->get();

// Methods
$variables = $template->extractVariables();
$isValid = $template->validateRequiredVariables($data);
$version = $template->createVersionSnapshot('reason');
```

### LeaseTemplateVersion
```php
$version = LeaseTemplateVersion::where('version_number', 2)->first();

// Relationships
$template = $version->template; // Parent template
$creator = $version->creator; // User who created

// Properties (immutable)
$version->version_number; // 2
$version->blade_content; // Full template content
$version->change_summary; // What was changed
$version->changes_diff; // Detailed diff
$version->created_by; // User ID
$version->created_at; // Timestamp
```

### Lease (Enhanced)
```php
$lease = Lease::find(1);

// Template references
$lease->lease_template_id; // Which template used
$lease->template_version_used; // Which version used

// Relationships
$template = $lease->leaseTemplate; // Template object
$version = $lease->leaseTemplateVersion; // Version object

// Methods (new)
$version = $lease->getCurrentTemplateVersion();
```

---

## INTEGRATION IN CONTROLLERS

### Download Lease PDF
```php
use App\Services\TemplateRenderServiceV2;

public function download(Lease $lease)
{
    $renderService = app(TemplateRenderServiceV2::class);
    
    // Render with versioned template
    $html = $renderService->renderLease($lease);
    
    // Generate PDF
    return PDF::loadHTML($html)
        ->download($lease->reference_number . '.pdf');
}
```

### Admin Create Template
```php
use App\Services\LeaseTemplateManagementService;

public function store(Request $request)
{
    $service = app(LeaseTemplateManagementService::class);
    
    $template = $service->createTemplate(
        $request->validated(),
        'Created by ' . auth()->user()->name
    );
    
    return redirect()->route('templates.show', $template)
        ->with('message', 'Template created');
}
```

### Admin Update Template
```php
public function update(Request $request, LeaseTemplate $template)
{
    $service = app(LeaseTemplateManagementService::class);
    
    $service->updateTemplate(
        $template,
        $request->validated(),
        $request->input('change_summary')
    );
    
    return redirect()->back()
        ->with('message', 'Template updated');
}
```

---

## LOGGING & DEBUGGING

### Enable Debug Mode
```php
// In .env
APP_DEBUG=true

// Services automatically log:
// - Template operations
// - Render operations
// - Errors with full context
```

### Check Logs
```bash
# Tail logs
tail -f storage/logs/laravel.log

# Filter for template operations
grep -i template storage/logs/laravel.log
```

### Manual Logging
```php
use Illuminate\Support\Facades\Log;

Log::info('Template operation', [
    'template_id' => $template->id,
    'version' => $template->version_number,
    'user_id' => auth()->id(),
]);
```

---

## ERROR HANDLING

### Common Exceptions

```php
// Template not found
ModelNotFoundException::class

// Validation failed
ValidationException::class

// Render failed
TemplateRenderException::class

// Version not found
VersionNotFoundException::class
```

### Error Response
```php
try {
    $html = $renderService->renderLease($lease);
} catch (Exception $e) {
    Log::error('Render failed', [
        'lease_id' => $lease->id,
        'error' => $e->getMessage(),
    ]);
    
    // Fallback
    return redirect()->back()
        ->with('error', 'Could not render template');
}
```

---

## QUEUED OPERATIONS

### Render in Queue
```php
use App\Jobs\RenderLeaseTemplate;

// Queue heavy operations
dispatch(new RenderLeaseTemplate($lease));
```

### Cleanup Old Versions
```php
use App\Jobs\ArchiveOldTemplateVersions;

// Schedule monthly cleanup
$this->job(ArchiveOldTemplateVersions::class)
    ->monthly();
```

---

## TESTING

### Unit Test Template Service
```php
public function test_create_template()
{
    $service = app(LeaseTemplateManagementService::class);
    
    $template = $service->createTemplate([
        'name' => 'Test',
        'template_type' => 'residential_major',
        'blade_content' => '<p>Test</p>',
    ], 'Test');
    
    $this->assertNotNull($template->id);
    $this->assertEquals(1, $template->version_number);
}
```

### Feature Test Rendering
```php
public function test_render_lease()
{
    $lease = Lease::factory()->create();
    $service = app(TemplateRenderServiceV2::class);
    
    $html = $service->renderLease($lease);
    
    $this->assertStringContainsString($lease->reference_number, $html);
}
```

---

## PERFORMANCE TIPS

1. **Cache Templates**
   ```php
   $template = Cache::remember(
       "template.{$id}",
       3600,
       fn() => LeaseTemplate::find($id)
   );
   ```

2. **Eager Load Versions**
   ```php
   $templates = LeaseTemplate::with('versions')->get();
   ```

3. **Index Queries**
   ```sql
   CREATE INDEX idx_template_type ON lease_templates(template_type);
   CREATE INDEX idx_template_active ON lease_templates(is_active);
   ```

4. **Archive Old Versions**
   ```php
   $service->archiveOldVersions($template, 30); // Keep 30 days
   ```

---

## REFERENCE FILES

- **Source Code:** `app/Services/LeaseTemplateManagementService.php`
- **Source Code:** `app/Services/TemplateRenderServiceV2.php`
- **Models:** `app/Models/LeaseTemplate.php`, `app/Models/LeaseTemplateVersion.php`
- **Admin:** `app/Filament/Resources/LeaseTemplateResource.php`
- **Technical Guide:** `TEMPLATE_VERSIONING_GUIDE.md`
- **Architecture:** `ARCHITECTURE_DIAGRAMS.md`

---

**Last Updated:** January 19, 2026  
**Version:** 1.0  
**For:** Developer Team
