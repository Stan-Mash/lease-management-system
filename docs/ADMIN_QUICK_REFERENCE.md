# ADMIN QUICK REFERENCE

**For:** Non-technical administrators managing lease templates  
**Purpose:** Quick guide to common tasks in the admin dashboard

---

## WHERE TO GO

**Admin Dashboard:** `https://your-site.com/admin`

**Templates Section:** Click **Lease Templates** in sidebar

---

## COMMON TASKS

### 📝 Create a New Template

1. Click **Create** button
2. Fill in:
   - **Name:** "Residential Major" (descriptive)
   - **Slug:** Auto-fills (keeps unique name for system)
   - **Type:** Choose from dropdown
   - **Description:** Optional notes about this template
   - **Blade Content:** Paste your template content
   - **CSS Styles:** Custom styles (optional)
3. Check **Is Active** if you want to use it
4. Check **Is Default** if this is the default for this type
5. Click **Create**
   - ✅ System automatically creates version 1
   - ✅ Sends you success message

---

### ✏️ Edit a Template

1. Find template in list
2. Click **Edit** button
3. Change any field
4. Click **Save**
   - ✅ System automatically creates new version
   - ✅ Old version stays in history
   - ✅ New version is now active

**What happens:**
- Version 1: Original (still there)
- Version 2: Your changes (now active)
- All leases created after now use version 2

---

### 📖 View Version History

1. Find template in list
2. Click **Version History** button
3. See all versions:
   - Version number (1, 2, 3...)
   - Date created
   - Who created it
   - What was changed

**Use this to:**
- See all changes over time
- Know who changed what
- Track audit trail

---

### 🔍 Compare Versions

1. In Version History, click **Compare**
2. Select two versions (e.g., v1 vs v2)
3. See side-by-side view:
   - What's different highlighted
   - Additions shown
   - Deletions shown

**Use this to:**
- Before restoring, see what will change
- Understand impact of each edit
- Document what was changed

---

### 👁️ Preview Template

1. Find template in list
2. Click **Preview** button
3. See rendered version with sample data

**Use this to:**
- See how it looks before using
- Verify no typos
- Check formatting

---

### ↩️ Restore Previous Version

1. Click **Version History**
2. Find the version you want
3. Click **Restore** button
4. Confirm: "Are you sure?"
5. ✅ Done!

**What happens:**
- Version gets restored
- New version created with restored content
- History preserved (nothing deleted)
- Audit trail complete

**Example:**
- v1: Original
- v2: Edit (mistake!)
- v3: Restore from v1
- v3 contains exact copy of v1
- v1 and v2 still exist in history

---

### 🗑️ Deactivate Template

1. Find template in list
2. Click **Deactivate**
3. ✅ Done - no longer used

**Note:** You can reactivate it later

---

### 📊 Check Usage Stats

1. Find template in list
2. Look at **Lease Count** column
3. Shows how many leases use this template

**Helps you:**
- Know before deleting
- See impact of changes
- Plan maintenance

---

## COMMON QUESTIONS

**Q: Can I undo a change?**  
A: Yes! Go to Version History and click Restore on the version you want.

**Q: What if I make a mistake?**  
A: No problem. Simply restore to the previous version. All history is kept.

**Q: Are old versions deleted?**  
A: Never! All versions are kept forever. You can always restore them.

**Q: Who can see the audit trail?**  
A: Other admins. Shows who made each change and when.

**Q: Can non-admins edit templates?**  
A: No. Only admins with access to this section.

**Q: What if I delete a template?**  
A: Existing leases still work. They keep the version they were created with.

**Q: Can I export a template?**  
A: Yes, copy the content and save locally.

**Q: Can I import a template?**  
A: Yes, create new template and paste content in.

---

## TIPS & BEST PRACTICES

### ✅ DO

- Use clear, descriptive names
- Write change summaries explaining why
- Preview before saving
- Check version history regularly
- Keep templates organized

### ❌ DON'T

- Don't copy-paste without testing
- Don't delete unless absolutely sure
- Don't forget to mark as active/default
- Don't make huge changes at once

---

## WHAT'S AUTOMATIC (You don't need to do this)

✅ Versioning - System creates versions automatically  
✅ History tracking - All changes recorded automatically  
✅ User attribution - System tracks who made changes  
✅ Timestamps - System records when changes happened  
✅ Change detection - System shows what changed  

---

## WHAT YOU CONTROL

📝 Template name and description  
📝 Template content (Blade code)  
📝 CSS styles  
📝 Active/Inactive status  
📝 Default template for type  
📝 Change summaries (why you made change)  

---

## TROUBLESHOOTING

**Template won't save?**
- Check for errors in Blade syntax
- Make sure name is filled in
- Try preview first

**Can't find template?**
- Use search box to find
- Check inactive templates
- Verify you have permission

**Renders are wrong?**
- Check template variables
- Verify lease has required data
- Try preview first

---

## SUPPORT

**Problem:** Use the admin interface as-is. It's self-explanatory.

**Questions:** Contact your IT/Developer team

**Bug Report:** Include:
- What you were doing
- What went wrong
- Screenshot if possible
- Time it happened

---

**Last Updated:** January 19, 2026  
**Version:** 1.0  
**For:** Admin Users
