# Commercial Lease Signatures & Execution Block — Professional Guide

## 1. Standard structure for commercial leases (Kenya practice)

A properly executed commercial lease in Kenya typically has **five signatory elements** in the execution block:

| # | Block | Role | Purpose |
|---|--------|------|--------|
| 1 | **Lessor / Assigned agents** | Landlord or managing agent | Party granting the lease |
| 2 | **In the presence of** (Witness 1) | Witness to lessor’s signature | Attests lessor’s execution |
| 3 | **Lessee** | Tenant | Party taking the lease |
| 4 | **In the presence of** (Witness 2) | Witness to lessee’s signature | Attests lessee’s execution |
| 5 | **Advocate’s stamp** | Advocate who drew/attested | Required for registration (Advocates Act) |

- **Lessor** and **Lessee** are the two main parties; each has a **witness** (“in the presence of”).
- The **advocate** may act as one or both witnesses, or appear as a separate attestation/stamp block depending on firm practice.
- For **stamp duty and registration**, the document must be stamped and (for leases over 2 years) registered; the advocate’s involvement supports that process.

---

## 2. How this maps to your system today

### 2.1 Current data you have

- **Lessee (tenant):** Digital signature captured (OTP-verified), stored in `digital_signatures` with `signer_type = 'tenant'`. Rendered on PDF.
- **Lessor / Assigned agents:** In Chabrin’s workflow, the **property manager** signs on behalf of the lessor/agency. Stored as `signer_type = 'manager'` in `digital_signatures`. Rendered as “Property Manager” / “Countersigned”.
- **Landlord (lessor) identity:** From `lease.landlord` (name, etc.); no separate lessor signature image.
- **Witnesses (“in the presence of”):** Not yet captured — no names or signature images in the app.
- **Advocate / lawyer:**  
  - Workflow: `with_lawyer` state and `lease_lawyer_tracking` (send to lawyer, receive back).  
  - **Not yet on the PDF:** No advocate name, firm, or stamp image on the final lease document.

### 2.2 Current commercial PDF issues

1. **Positioning / structure**  
   - “In the presence of” and “ADVOCATE” are currently placed as if one block under the lessor, and again at the bottom. The intended structure is: **Lessor (+ witness)** → **Lessee (+ witness)** → **Advocate stamp**.

2. **Duplicate “in the presence of”**  
   - There are two “in the presence of” / advocate areas (one under lessor, one after lessee in the physical branch). They should be clearly separated: one witness block per party, then a single advocate block.

3. **Lawyer step not on document**  
   - The workflow has a “With Lawyer” step, but the final lease PDF does not show:  
     - Advocate name/firm  
     - Advocate stamp (image or text)  
     - Date of attestation/stamping  

So the **document** does not yet reflect the **lawyer step** that exists in the **workflow**.

---

## 3. Recommended execution block layout (commercial)

A clear, modern layout that matches the 5 signatory elements:

```
IN WITNESS whereof the Parties have hereunto set their respective hands the day and year first herein written.

────────────────────────────────────────────────────────────────────────────
1. LESSOR / ASSIGNED AGENTS
────────────────────────────────────────────────────────────────────────────
   SIGNED by the said ............................ (the Lessor/Assigned agents)
   [Signature image or line: Property Manager]

   Signature
   in the presence of:
   ............................ (Witness name / Advocate)
────────────────────────────────────────────────────────────────────────────
2. LESSEE
────────────────────────────────────────────────────────────────────────────
   SIGNED by the Lessee ............................
   [Tenant digital signature image]

   Signature
   in the presence of:
   ............................ (Witness name / Advocate)
────────────────────────────────────────────────────────────────────────────
3. ADVOCATE'S STAMP
────────────────────────────────────────────────────────────────────────────
   [Advocate stamp image or text: Name, Firm, Date]
   (Optional: “Drawn by / Attested by”)
────────────────────────────────────────────────────────────────────────────
ELECTRONIC EXECUTION RECORD (if digital)
...
```

- **Positioning:** Lessor block first, then Lessee block, then a single Advocate block at the end. Each “in the presence of” sits directly under the signature it attests.
- **No duplicate “in the presence of”:** One witness line per party; advocate can be named there and/or in the advocate stamp block.

---

## 4. How to incorporate this in your system

### 4.1 Signature positioning (quick win)

- **Commercial Blade view:** `resources/views/pdf/commercial.blade.php`  
  - Reorder and label sections explicitly: **1. Lessor/Assigned agents** (with manager signature) → **2. Lessee** (tenant signature) → **3. Advocate stamp**.
  - Under **Lessor:** one “Signature” line and one “in the presence of” (witness 1).
  - Under **Lessee:** one “Signature” line and one “in the presence of” (witness 2).
  - One **Advocate** block at the end (stamp/name/firm/date), with no duplicate “in the presence of” for the whole document.

This gives correct **positioning** and removes the feeling of a duplicate “in the presence of” and advocate line.

### 4.2 Witness names (“in the presence of”)

- **Option A (simple):** Add two optional fields to the lease (e.g. `witness_lessor_name`, `witness_lessee_name`) or to the lease template. Show them on the PDF under the respective “in the presence of” lines. No signature images for witnesses.
- **Option B (full):** If you later need witness signatures, you could add “witness” as a signatory type and capture names and optionally images. For many commercial leases, names alone under “in the presence of” are sufficient.

Start with **Option A** so the document clearly shows who witnessed each party.

### 4.3 Advocate stamp and lawyer step

- **Data:**  
  - When a lease is “with lawyer” or “returned” from lawyer, you already have (or can add): lawyer name, firm, date returned.  
  - Optionally: upload an **advocate stamp image** (e.g. PNG) per lawyer or per lease.

- **PDF:**  
  - Add a **third block** on the commercial (and optionally other) lease type: “Advocate’s stamp”.  
  - If you have a stamp image: show it (e.g. from `lawyers.stamp_path` or `lease_lawyer_tracking`).  
  - Else: show text (e.g. “Drawn/Attested by: [Name], [Firm], [Date]”).  
  - Only show this block when the lease has been to lawyer (e.g. has a `lease_lawyer_tracking` “returned” record or a dedicated “advocate_stamp_date” on the lease).

- **Workflow:**  
  - Keep the existing “With Lawyer” step.  
  - When the lawyer returns the lease, staff can mark “returned” and optionally attach/select advocate stamp.  
  - Final PDF generation then includes the advocate block so the **document** reflects the **lawyer step**.

### 4.4 Lessor signature

- You already treat **Property Manager** as signing for the lessor/assigned agents.  
- Keep that; the execution block should label it clearly as “Lessor/Assigned agents” and show the manager’s signature there.  
- If you later need a **landlord’s own** signature (separate from manager), you could add a lessor signatory type and capture flow; for now, manager-on-behalf-of-lessor is consistent with the 5-element structure.

---

## 5. Implementation order (recommended)

| Priority | Task | Purpose |
|----------|------|--------|
| 1 | **Fix commercial Blade layout** | Correct order: Lessor (+ witness) → Lessee (+ witness) → Advocate. Remove duplicate “in the presence of”/advocate. Use manager signature for lessor block. |
| 2 | **Pass lawyer/advocate data to PDF** | When lease has lawyer tracking (returned), pass lawyer name/firm/date (and optionally stamp image) to the view so the advocate block can be filled. |
| 3 | **Add advocate block to commercial view** | Third section: “Advocate’s stamp” (image or text). Show only when lawyer data exists. |
| 4 | **(Optional) Witness name fields** | Add `witness_lessor_name`, `witness_lessee_name` (or similar) and show under the two “in the presence of” lines. |

---

## 6. Summary

- **Positioning:** Commercial lease execution should have three clear sections: (1) Lessor/Assigned agents + one “in the presence of”, (2) Lessee + one “in the presence of”, (3) Advocate’s stamp. No duplicate “in the presence of” or advocate line elsewhere.
- **Signatures:** Lessor = property manager (current behaviour); Lessee = tenant digital signature. Both are already in the system; only placement and labels need adjustment.
- **Lawyer step:** Already in workflow; add lawyer/advocate data to PDF data and an “Advocate’s stamp” block on the final document so the signed lease reflects that step and stays registration-ready.

Implementing section 5 in order will align the final lease document with the 5 signatory elements and make the execution block clear and professional.

---

## 7. Approach: use the PDF as provided

The commercial lease PDF template is **used as provided by the client**. No layout or structure changes are made to the client’s document. The system adapts to it: tenant and manager signatures (and any existing placeholders) are rendered into the client’s layout as-is.
