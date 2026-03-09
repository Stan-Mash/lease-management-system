# Why Lease Templates Change & How to Restore

## Where to put reference lease PDFs (so the system can replicate them)

**Folder:** `storage/app/templates/leases`  
(e.g. `C:\Users\kiman\Projects\chabrin-lease-system\storage\app\templates\leases`)

Place your Chabrin lease PDFs here. These PDFs are the **only** source of default lease templates.

**To make these PDFs the sole templates (recommended):**

```bash
php artisan templates:use-pdf-only
```

This removes all existing lease templates and creates one template per PDF in this folder, each set as the default for its type (commercial, residential_major, residential_micro). PDFs are copied to public storage and linked as each template’s source PDF.

**Alternative — add/update without removing others:**

```bash
php artisan templates:import --force
```

- **Filename hints:** Template type is inferred from the filename: `commercial` → commercial, `major` → residential_major, `micro` → residential_micro.
- After import, open **Settings → Lease Templates** in the admin to edit or use **Pick positions** to map fields on the PDF.

---

## Why the current templates might not be the “original” ones

**Lease templates are now sourced only from PDFs** in `storage/app/templates/leases`. The system no longer uses seeders or hardcoded Blade for the three types (residential major, residential micro, commercial). To (re)build defaults from your PDFs, run:

```bash
php artisan templates:use-pdf-only
```

This removes any existing templates and creates one template per PDF in that folder, each set as the default for its type. If you previously had custom content, it can only be restored from **version history** (Restore from version in the admin) or from a database backup.

**What is not stored in version history**

- **Uploaded PDF** (`source_pdf_path`)  
- **Coordinate map** (`pdf_coordinate_map`) – the “Pick positions” mapping  

Those fields live only on `lease_templates`. They are **not** in `lease_template_versions`, so they **cannot** be restored from “Restore from version”. They can only be recovered from:

- A database backup from before the overwrite, or  
- Re-uploading the PDF and re-doing “Pick positions”.

---

## How to restore the original template content (Blade, CSS, variables)

If the overwrite happened **after** you had already edited the templates at least once, the system will have saved **version snapshots** in `lease_template_versions`. You can restore that content from the UI.

### Restore from version (admin UI)

1. Go to **Settings → Lease Templates**.
2. Open the template you want to restore (e.g. Residential Major).
3. Either:
   - Click **Edit**, or  
   - Stay on **View**.
4. In the header, click **“Restore from version”**.
5. In the modal, choose the **version** you want (list shows version number, date, and summary).
6. Click the action to confirm.  
   The template’s **Blade content, CSS, layout, branding, and variables** are restored from that version. You are redirected to Edit so you can see the restored content.
7. **Save** the template if you want this restored state to become the new current version.

**Note:** Restoring does **not** bring back the uploaded PDF or the coordinate map. If you need those, restore from a DB backup or re-upload and re-map.

### If there are no versions to restore

If the template was overwritten by a seeder **before** any manual edit had been saved, there may be no older version in `lease_template_versions` that contains your original content. In that case:

- The only way to get the exact previous content back is from a **database backup** (e.g. from `php artisan db:backup`) taken before the seeder was run.
- To avoid this in future: keep your authoritative PDFs in `storage/app/templates/leases` and use `php artisan templates:use-pdf-only` when you need to reset or refresh templates from those PDFs.

---

## Summary

| What happened | Why | What you can do |
|---------------|-----|------------------|
| Template text/layout looks like the seeder default | Seeders overwrite by slug | Use **Restore from version** if an older version exists |
| No “Restore from version” or no older versions | No version was saved before overwrite | Restore from a DB backup, or re-enter content |
| Uploaded PDF / “Pick positions” mapping gone | Not stored in version history | Restore from DB backup, or re-upload PDF and re-do Pick positions |
