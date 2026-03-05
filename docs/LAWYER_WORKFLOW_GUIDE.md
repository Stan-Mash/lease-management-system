# Lawyer Workflow: Send to Lawyer & Wait for Return

## 1. What exists today

### Workflow step

- **State:** `with_lawyer` exists in `LeaseWorkflowState`.
- **Transitions:**
  - From **Tenant Signed:** you can move to `with_lawyer`, `pending_upload`, `pending_deposit`, or `active`.
  - From **With Lawyer:** you can move to `pending_upload` or `pending_deposit`.
- So the **step** (send to lawyer → wait → come back) is in the workflow design.

### Data model

- **`lease_lawyer_tracking`** table and **`LeaseLawyerTracking`** model record each “send to lawyer” round:
  - **Sending:** `sent_method` (email | physical), `sent_at`, `sent_by`, `sent_notes`
  - **Return:** `returned_method` (email | physical), `returned_at`, `received_by`, `returned_notes`
  - **Tracking:** `turnaround_days`, `status` (pending | sent | returned | cancelled)
- **`Lawyer`** model: name, firm, phone, email, etc. (CRUD via Filament LawyerResource).
- **Permissions:** `send_to_lawyer`, `receive_from_lawyer` (and `manage_lawyers`).

### What is **not** implemented yet

- **No “Send to Lawyer” action** on the lease view (no button to assign a lawyer, send the lease, and transition to `with_lawyer`).
- **No “Mark Returned from Lawyer” action** (no button to record that the lease came back and optionally attach the stamped PDF).
- **No lawyer-facing link or portal.** The lawyer does **not** get a link to download the lease, stamp it, and re-upload so it lands back in the system automatically.

So today you have the **state and tracking structure**, but no UI to drive the step and no link-based flow for the lawyer.

---

## 2. How the “link” flow is usually done (research)

Common patterns in legal / document workflows:

### Pattern A: Manual (email or physical)

- Staff **sends** the lease to the lawyer (email attachment or physical).
- Lawyer **downloads**, stamps/reviews offline, and returns by **email or in person**.
- Staff **manually** marks “Returned” in the system and, if needed, **uploads** the stamped PDF as a lease document.

No link for the lawyer; everything is manual. Your current data model (`sent_method` / `returned_method`: email | physical) fits this.

### Pattern B: Lawyer portal / single link (download + re-upload)

- System generates a **secure, time-limited link** (e.g. signed URL or token) and sends it to the lawyer (e.g. by email).
- Lawyer opens the link and can:
  - **Download** the current lease PDF.
- Lawyer stamps/reviews **offline** (or in another tool).
- Lawyer opens **the same link again** (or a dedicated “upload” page with the same token) and **uploads** the stamped PDF.
- System **receives** the file, stores it (e.g. as a lease document or “lawyer-returned” version), and can **automatically** mark the tracking as “returned” and optionally move the lease to `pending_upload` or `pending_deposit`.

So: **one link** can serve both “download” and “upload back”; the document comes back into the system directly when the lawyer uploads. This is the “link → download → stamp → re-upload to same link → system gets it” flow you asked about.

### Security and behaviour

- **Signed / tokenised URLs** are time-limited (e.g. 7–14 days). After expiry, the link no longer works.
- **One-time use** is possible but not required: often the link is “multi-use until expiry” so the lawyer can open it to download and again to upload. True one-time use needs extra logic (e.g. invalidate token after first upload).
- **Same link for download and upload** is a common, user-friendly pattern; the page at that link can show:
  - “Download lease PDF”
  - “Upload stamped/signed PDF” (single file upload).
- No need for the lawyer to log into the main admin panel; the link is the only access.

---

## 3. Recommended direction

### Phase 1: Use the system as designed (manual flow)

Implement the **existing** design first, without a lawyer link:

1. **“Send to Lawyer” action** (on lease view, when state is `tenant_signed` and e.g. `requires_lawyer` is true):
   - Select lawyer (from `Lawyer`).
   - Choose send method: **Email** or **Physical**.
   - If Email: send an email to the lawyer with the lease PDF attached (and optional message).
   - Create a **`LeaseLawyerTracking`** row (lease_id, lawyer_id, sent_method, sent_at, sent_by, sent_notes, status = `sent`).
   - Transition lease to **`with_lawyer`**.

2. **“Mark Returned from Lawyer” action** (when lease is `with_lawyer` and there is a tracking in status `sent`):
   - Choose return method: **Email** or **Physical**.
   - Optional: **upload the stamped PDF** (store as lease document, e.g. type “lawyer_stamped” or “original_signed”).
   - Call `LeaseLawyerTracking::markAsReturned(...)` (returned_method, received_by, returned_notes).
   - Optionally auto-transition lease to **`pending_upload`** (or keep in `with_lawyer` until staff moves it).

No link for the lawyer; they receive the PDF by email (or physically), stamp it, and return it; staff records return and uploads the file. This matches your current DB and permissions.

### Phase 2 (optional): Lawyer link for download + re-upload

If you want the lawyer to use **one link** to download and then re-upload so the file comes back into the system directly:

1. **Link generation**
   - When staff clicks “Send to Lawyer” and chooses “Send link” (or a new option “Link” in addition to Email/Physical):
   - Generate a **signed URL** (Laravel `URL::temporarySignedRoute(...)`) or a token stored in `lease_lawyer_tracking` (e.g. `lawyer_link_token`, `lawyer_link_expires_at`).
   - Send the lawyer an **email** containing this link (and lease reference).

2. **Lawyer portal (single route)**
   - Route: e.g. `GET/POST /lawyer/lease/{token}` (or `.../{lease}/{token}`).
   - **GET:** Show a simple page: “Lease XYZ – Download PDF” (link to generated PDF) and “Upload stamped PDF” (form with file input + submit).
   - **POST:** Accept one file (PDF), validate size/type, store in your app (e.g. `storage/app/private/lawyer-returns/{lease_id}/{id}.pdf`) and attach as lease document; mark tracking as **returned** and optionally transition lease to **`pending_upload`**; show “Thank you, document received.”

3. **Expiry**
   - Signed route: e.g. 14 days. Token in DB: enforce `lawyer_link_expires_at` when the lawyer opens the page or uploads.

So: **yes**, the idea “link → lawyer downloads → stamps → re-uploads to same link → system gets it” is the right pattern and can be added on top of Phase 1.

---

## 4. Direct answers to your questions

- **Is there a step for sending to lawyer and waiting for it to be sent back?**  
  **Yes** in the workflow (state `with_lawyer` and `LeaseLawyerTracking`). **No** in the UI: there is no “Send to Lawyer” or “Mark Returned” action yet.

- **Should the lawyer get a link, download, stamp, and re-upload to the same link so it comes back to the system?**  
  That’s a **good and common** approach. It is **not** built yet. You can add it (Phase 2) so the lawyer uses one link to download the PDF and upload the stamped PDF, and the system stores the file and marks the lease as returned.

- **How should this go?**  
  - **Short term:** Add the **Send to Lawyer** and **Mark Returned from Lawyer** actions (Phase 1, manual email/physical).  
  - **Later (optional):** Add a **lawyer link** (signed URL or token) that allows **download + re-upload** so the stamped document comes back into the system directly (Phase 2).

---

## 5. Summary table

| Item | Status | Notes |
|------|--------|--------|
| Workflow state `with_lawyer` | Exists | tenant_signed → with_lawyer → pending_upload / pending_deposit |
| LeaseLawyerTracking (sent/returned) | Exists | email/physical; no link field yet |
| “Send to Lawyer” button/action | Missing | Needs to be added (select lawyer, send email or record physical, create tracking, set state) |
| “Mark Returned from Lawyer” action | Missing | Needs to be added (record return, optional upload of stamped PDF, mark tracking returned) |
| Lawyer link (download + re-upload) | Missing | Optional: signed URL or token, one page for download + upload, file lands in system and tracking marked returned |

Implementing Phase 1 gives you a complete “send to lawyer and wait for return” step using the PDF and process as the client has provided; Phase 2 adds the link-based flow so the lawyer can re-upload directly to the same link and the system receives the document.
