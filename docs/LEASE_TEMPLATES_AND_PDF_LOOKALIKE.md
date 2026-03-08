# Why Lease Templates Don’t Look Like the Uploaded PDF

## Short answer

When you **upload a PDF**, the system only **extracts plain text** and puts it into a **fixed HTML layout**. It does **not** copy the PDF’s design (sections, tables, fonts, spacing). So the generated lease looks like a simple text document, not a clone of your PDF.

To get output that **does** look like the uploaded PDF, you must use **“fill the PDF”** (see below).

---

## What actually happens when you upload a PDF

1. **Text-only extraction**  
   The app uses `Smalot\PdfParser` and calls `$pdf->getText()`. That returns **only the text** from the PDF. It does **not** keep:
   - Layout (columns, tables, sections)
   - Fonts, sizes, or styles
   - Images or logos
   - Exact positions or spacing

2. **Generic HTML wrapper**  
   That text is then dropped into a **single, standard layout**:
   - A simple header (title + reference)
   - One content block with `white-space: pre-wrap` and the extracted text

   So you get one block of text with basic styling (e.g. Arial, 11px), not the structure of your original PDF.

3. **Pattern-based placeholders**  
   The code does regex replacements (e.g. “Tenant: X” → “Tenant: {{ $tenant->names }}”) to insert Blade variables. That doesn’t restore or preserve the PDF’s layout.

**Result:** The generated lease is a simple, generic-looking document. It will **not** look like your branded PDF.

---

## How to get output that looks like the uploaded PDF

The codebase already supports a **“fill the uploaded PDF”** path. When **`source_pdf_path`** is set, the system uses the uploaded PDF as the base (coordinate map is optional):

- **`source_pdf_path`** – path to the uploaded PDF (e.g. from the “PDF Upload” tab)
- **`pdf_coordinate_map`** – positions (page, x, y, and optionally width/height) for each field and for signatures

the system **does not** rebuild the document from Blade. It:

1. Uses the **uploaded PDF** as the base.
2. **Stamps** lease data (tenant name, rent, dates, etc.) at the coordinates you define.
3. **Stamps** tenant and manager signatures at their coordinates.

So the final PDF **is** your uploaded PDF with fields filled in. It will look like the original.

### Coordinate map shape

- **Text fields**: each entry needs at least `page`, `x`, `y`. Optional: `size` (font size in pt, default **12**), `width` (mm, keeps text in box), `align` (`L`|`C`|`R`), `color` (hex e.g. `000000`).
- **Signatures**: entries for `tenant_signature`, `manager_signature`, etc. with `page`, `x`, `y`, `width`, `height`.

**Dates:** The document often has separate boxes for day / month / year (with slashes pre-printed). Use the **split** keys so the system does not add slashes:

- `lease_date_day`, `lease_date_month`, `lease_date_year` — for “dated the __ day on the month of __ in the year __”
- `start_date_day`, `start_date_month`, `start_date_year` — for “from __ / __ / __”
- `end_date_day`, `end_date_month`, `end_date_year` — for “To __ / __ / __”

Single-field keys `start_date` and `end_date` are formatted as `d-m-Y` (no slashes).

Field keys (must match overlay fields): `lease_date_day`, `lease_date_month`, `lease_date_year`, `start_date_day`, `start_date_month`, `start_date_year`, `end_date_day`, `end_date_month`, `end_date_year`, `landlord_name`, `landlord_po_box`, `tenant_name`, `tenant_id_number`, `tenant_po_box`, `property_name`, `property_lr_number`, `unit_code`, `monthly_rent`, `deposit_amount`, `vat_amount`, `start_date`, `end_date`, `reference_number`, plus signature keys.

Example (conceptual):

```json
{
  "tenant_name":   { "page": 1, "x": 120, "y": 180, "size": 12, "width": 80, "align": "L" },
  "monthly_rent": { "page": 1, "x": 120, "y": 220, "size": 12 },
  "start_date_day":   { "page": 1, "x": 100, "y": 260 },
  "start_date_month": { "page": 1, "x": 115, "y": 260 },
  "start_date_year":  { "page": 1, "x": 130, "y": 260 },
  "tenant_signature": { "page": 2, "x": 140, "y": 240, "width": 80, "height": 30 },
  "manager_signature": { "page": 2, "x": 140, "y": 280, "width": 80, "height": 30 }
}
```

Coordinates are in mm. Use `width` so stamped text stays inside the printed box; font size defaults to 12 pt.

### Where to set this

**Option 1 — Coordinate picker (recommended)**

1. Go to **Lease Templates** in the admin.
2. Open a template that has a **source PDF** (uploaded on the PDF Upload tab).
3. Click **“Pick positions on PDF”** or **“Pick positions”**.
4. On the picker page:
   - Select a field (e.g. “Tenant name”) in the left panel.
   - Click on the PDF where that value should appear.
   - Repeat for all fields (text fields and signature areas).
   - Use **Prev / Next** to switch pages on multi-page PDFs.
5. Click **“Save coordinate map”**. Generated leases will now use your uploaded PDF with data stamped at those positions.

**Option 2 — Manual JSON**

- On the **Edit** page, open the **PDF Upload** tab. After uploading the PDF, paste JSON into **“PDF coordinate map”**. Example format is in the placeholder. Save.

**Option 3 — Apply default “Particulars” map**

If your PDF follows the standard “Particulars” layout (date, lessor, lessee, building, term, rent, deposit, VAT on page 1), you can apply a built-in coordinate map so text aligns in the boxes (font 12, width/align). Only applies to templates that have a source PDF and **no** existing map (use `--force` to overwrite):

```bash
php artisan templates:apply-default-coordinates
```

Optional: `--type=commercial` (or `residential_major` / `residential_micro`), `--force` to overwrite existing maps. After running, fine-tune via **Pick positions** if your PDF layout differs.

---

## Troubleshooting: PDF upload errors

If the PDF Upload tab shows "Error during upload" or "source_pdf_path failed to upload":

1. **Livewire temp disk** — The app uses `temporary_file_upload.disk = 'public'` so temp uploads go to `storage/app/public`. Ensure that directory is writable by the web server.

2. **On the server** (with sudo if needed):
   ```bash
   mkdir -p storage/app/public/livewire-tmp storage/app/public/templates/source-pdfs
   sudo chown -R www-data:www-data storage
   sudo chmod -R 775 storage
   php artisan storage:link
   php artisan config:clear
   ```

3. **PHP limits** — `upload_max_filesize` and `post_max_size` ≥ 10M in php.ini.

4. **Nginx** — `client_max_body_size` ≥ 10M in the server block.

### "Error while loading page" when clicking Save

If the edit form shows this generic error on save:

1. **Check PHP limits** — Lease template forms (blade_content, etc.) can be large. Ensure `php.ini` has:
   - `post_max_size` = 20M or higher
   - `upload_max_filesize` = 20M or higher

2. **Check Laravel log** — On the server: `tail -50 storage/logs/laravel.log` for the actual exception (validation, database, etc.).

3. **Save in steps** — If you only changed the PDF Upload tab, try saving the Basic Information tab first, then the PDF, to reduce payload size.

---

## Summary

| Approach | What happens | Looks like uploaded PDF? |
|----------|--------------|---------------------------|
| Upload PDF only (no coordinate map) | Uses uploaded PDF as-is; no data stamped | **Yes** – same layout (tenant/rent blank) |
| Upload PDF + **pdf_coordinate_map** | Original PDF + data and signatures stamped at coordinates | **Yes** – filled lease matching your PDF |
| Custom Blade template | You write HTML/Blade to match your design | Only if you design it to match |

So: **lease templates are not similar to the uploaded PDF by default** because the pipeline is “text extraction + generic layout”. To make them match, use the **uploaded PDF plus a coordinate map** so the system fills your PDF instead of generating a new layout.
