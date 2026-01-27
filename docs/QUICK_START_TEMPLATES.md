# 🚀 QUICK START: Template Versioning System

**Time to deploy**: 15 minutes  
**Complexity**: Low (system is pre-built)

---

## STEP 1: Run Database Migrations

```bash
# Ensure all tables exist
php artisan migrate

# Output should show:
# - Migrated: 2026_01_19_033059_create_lease_templates_table
# - Migrated: 2026_01_19_033157_create_lease_template_versions_table
# - Migrated: 2026_01_19_033159_create_lease_template_assignments_table
# - Migrated: 2026_01_19_033201_add_template_id_to_leases_table
```

---

## STEP 2: Import Your PDF Templates

```bash
# This creates 3 templates with version history
php artisan leases:import-templates

# Expected output:
# 🚀 Starting Lease Template Import System...
# 
# 📄 Processing: Residential Major - Chabrin Agencies
#    ✓ Created template v1 (ID: 1)
# 
# 📄 Processing: Residential Micro - Chabrin Agencies
#    ✓ Created template v1 (ID: 2)
# 
# 📄 Processing: Commercial - Chabrin Agencies 2022
#    ✓ Created template v1 (ID: 3)
# 
# ✅ Template import complete!
# Total Templates: 3
# Total Versions: 3
```

---

## STEP 3: Access Admin Dashboard

```
URL: http://localhost/admin/lease-templates

You should see:
- List of 3 templates (Residential Major, Micro, Commercial)
- Each showing v1
- All marked as "Active" and "Default"
```

---

## STEP 4: Test Rendering

```php
// In Tinker or a test controller
php artisan tinker

// Get service
$service = app(App\Services\TemplateRenderService::class);

// Get a test lease
$lease = App\Models\Lease::first();

// Render with versioned template
$html = $service->renderLease($lease);

// Check output
echo strlen($html); // Should be > 0
echo $lease->template_version_used; // Should be set
```

---

## STEP 5: Verify Version Tracking

```php
// Check template versions
$template = App\Models\LeaseTemplate::first();

// See all versions
$versions = $template->versions;
echo $versions->count(); // Should be 1

// Get version history
$service = app(App\Services\LeaseTemplateManagementService::class);
$history = $service->getVersionHistory($template);
print_r($history);
```

---

## STEP 6: Test Version Control

```php
// Make a template change
$template = App\Models\LeaseTemplate::first();

// Update some content
$template->update([
    'blade_content' => 'Modified content here...',
    'is_active' => true,
]);

// Check that new version was created
echo $template->version_number; // Should be 2

// Get versions
$versions = $template->versions;
echo $versions->count(); // Should be 2
```

---

## STEP 7: View Version History in Admin

```
1. Go to: /admin/lease-templates
2. Click on "Residential Major - Chabrin Agencies"
3. Scroll to "Lease Template Versions" section
4. You should see:
   - Version 1 (created during import)
   - Version 2 (from your test change)
```

---

## WHAT'S NOW WORKING

✅ **Templates stored in database** (not hardcoded)  
✅ **Version 1 created for each** (immutable history)  
✅ **Every edit auto-versions** (automatic tracking)  
✅ **Leases bind to versions** (consistency guaranteed)  
✅ **Full audit trail** (who changed what when)  
✅ **Admin dashboard** (manage templates easily)  

---

## NEXT: EXTRACT EXACT PDF CONTENT

The system is ready. Now you need to:

1. **Review your original PDFs** (the 3 you attached)
2. **Extract exact structure** (sections, fields, layout)
3. **Create matching Blade templates** that render identically
4. **Replace the placeholder content** in the templates
5. **Test rendering** against originals

### Get the PDF Content

Your PDFs are stored in:
```
storage/app/templates/leases/
- CHABRIN AGENCIES TENANCY LEASE AGREEMENT - MAJOR DWELLING.pdf
- CHABRIN AGENCIES TENANCY LEASE AGREEMENT - MICRO DWELLING.pdf
- COMMERCIAL LEASE - 2022 (2) (1).pdf
```

### Update Template Content

Edit in Admin:

```
/admin/lease-templates/{id}/edit

Update the "Template Content" section:
- Replace blade_content with exact structure from PDF
- Add CSS styling to match PDF formatting
- Test rendering with sample data
- Save (auto-creates v2)
```

---

## TESTING CHECKLIST

Before considering complete:

```
□ Created 3 templates in database
□ Each template has version 1
□ Templates marked as active and default
□ Admin dashboard accessible
□ Can edit template in admin
□ New versions created automatically
□ Version history shows changes
□ Can render lease with template
□ Lease locked to template version
□ PDF output matches original
```

---

## COMMON ISSUES

### "Tables don't exist"
```bash
php artisan migrate
# Check migrations ran
php artisan migrate:status
```

### "Templates not imported"
```bash
php artisan leases:import-templates --force
# Check with: LeaseTemplate::all();
```

### "Admin page blank"
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Then visit /admin/lease-templates again
```

### "Template not rendering"
```php
$service = app(App\Services\TemplateRenderService::class);
$errors = $service->validateBeforeRender($template, $lease);
print_r($errors); // Will show what's wrong
```

---

## KEY COMMANDS

```bash
# Import templates
php artisan leases:import-templates

# Clear cache (if needed)
php artisan cache:clear

# Check template count
php artisan tinker
>>> App\Models\LeaseTemplate::count();

# View version history
>>> $t = App\Models\LeaseTemplate::first();
>>> $t->versions->each(fn($v) => echo "v{$v->version_number}: {$v->change_summary}\n");

# Test rendering
>>> $service = app(App\Services\TemplateRenderService::class);
>>> $html = $service->renderLease($lease);
```

---

## WHAT TO DO NOW

1. ✅ **Run migrations** - `php artisan migrate`
2. ✅ **Import templates** - `php artisan leases:import-templates`
3. ✅ **Access admin** - `http://localhost/admin/lease-templates`
4. ⏭️ **Extract PDF content** - Get exact structure from your PDFs
5. ⏭️ **Update templates** - Replace placeholder content with real content
6. ⏭️ **Test rendering** - Compare output with original PDFs
7. ⏭️ **Go live** - Update lease generation to use versioned system

---

## USEFUL DOCUMENTATION

- [Full Implementation Guide](TEMPLATE_VERSIONING_GUIDE.md) - Complete architecture
- [Implementation Checklist](IMPLEMENTATION_CHECKLIST_TEMPLATES.md) - Step-by-step tasks
- Code files:
  - `app/Services/LeaseTemplateManagementService.php` - Template lifecycle
  - `app/Services/TemplateRenderServiceV2.php` - Rendering engine
  - `app/Filament/Resources/LeaseTemplateResource.php` - Admin UI
  - `app/Console/Commands/ImportLeaseTemplatesFromPDF.php` - Import tool

---

## YOU'RE NOW READY! 🎉

The hard part (architecture, database, versioning, services) is done.

Next: Make the Blade templates match your PDFs exactly.

Questions? Check the full guide or explore the code - it's all well-documented.
