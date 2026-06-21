# Test Plan — Document Collection (Phase B)

> **Scope:** Manually verify the new Document Collection module — the 5 new masters, the checklist-template-driven document set, the lean-create → tabbed-edit flow, per-document status/attachments/rejection, the verification checklist, follow-up/outcome, signature, and the index list/filters.
> **Base URL:** `http://mquik.test` · **Date:** 2026-06-03
> **Pre-req:** `npm run dev` (Blade changes); log in as admin/super-admin. Seeded data already present (3 sample collections).

---

## 1. New masters (reuse-first design)

| # | Step | Expected |
|---|---|---|
| 1.1 | Sidebar → **Insurance** group | See **Document Collection** + the 5 masters: **Claim Types, Insurance Policy Types, Missing Document Reasons, Document Rejection Reasons, Follow-up Modes**. |
| 1.2 | Open each master | Seeded values: Claim Types (Cashless/Reimbursement/Accident/Theft/Total Loss); Policy Types (Comprehensive/Third Party/Zero Dep/Corporate); Follow-up Modes (Call/SMS/WhatsApp/Email/Visit); plus the two reason masters. |
| 1.3 | Create / edit / deactivate one row in any master | Saves uppercase; inactive rows drop out of the Document Collection pickers. |
| 1.4 | **Document checklist is NOT a new master** — confirm | Sidebar → **Inspection/Workshop → Checklist Templates** has **INSURANCE CLAIM DOCUMENTS** + **CLAIM VERIFICATION** (group INSURANCE CLAIM). These drive the document/verification lists (reused framework, as designed). |

---

## 2. Lean create (`/document-collections/create`)

| # | Step | Expected |
|---|---|---|
| 2.1 | **Insurance → Document Collection → New Collection** | Lean form: Customer & Vehicle, Source & Purpose, Insurance, Checklist — **no** Documents/Verification yet, with a hint they unlock after create. |
| 2.2 | Customer → then Vehicle (vehicle disabled until customer picked) | Vehicle list scopes to the chosen customer. |
| 2.3 | Request Source = **Insurance Claim**; Insurance Company + Policy Type + Claim Type + Policy No.; **Document Checklist = INSURANCE CLAIM DOCUMENTS**; Verification Checklist = CLAIM VERIFICATION | All selectable. |
| 2.4 | **Create Collection** | Saves; toast `DC-00004 created.`; **redirects into the editor** (tabs now visible). Status badge = Pending. |

---

## 3. Documents tab (rich edit)

| # | Step | Expected |
|---|---|---|
| 3.1 | Open the **Documents** tab | Pre-seeded from the chosen template — RC Book, Insurance Policy, DL, Aadhar/ID, PAN, Claim Intimation, FIR, Claim Form, Damage Photos, PUC — each with a Status select. |
| 3.2 | Set a document → **Received** + attach a JPG/PNG/**PDF** | File chooser accepts images + PDF; after save a "View file" link appears on that row. |
| 3.3 | Set a document → **Rejected** | A **Rejection Reason** picker appears in place of the notes field; pick one. |
| 3.4 | **Add document** (manual row) + type a label; remove a row with ✕ | Row adds/removes; reorders cleanly. |
| 3.5 | **Save Changes** | No errors; files stored; the parent's `uploaded_at` stamps once a file is attached. Re-open → received/rejected statuses, the file link, and rejection reason all persisted. |
| 3.6 | Documents tab count in the tab label | Reflects the number of document rows. |

---

## 4. Verification & Sign-off tab

| # | Step | Expected |
|---|---|---|
| 4.1 | Open **Verification & Sign-off** | Verification checklist pre-seeded from CLAIM VERIFICATION (Name Match, Vehicle Number Match, Policy Validity, Signature Match), each a checkbox. |
| 4.2 | Tick some checks + add a manual check + add a note | Checks toggle; add/remove works. |
| 4.3 | **Follow-up & Outcome**: set Missing Document Reason, Rejection Reason, Follow-up Mode + Notes | All from their masters; saved. |
| 4.4 | Upload a **customer signature** → Save → re-open | Signature shows "on file" with a Remove control; Remove clears it. |
| 4.5 | Change **Status** (Details tab) to Received / Rejected / Cancelled → Save | Status badge + index reflect it. |

---

## 5. Index list & filters (`/document-collections`)

| # | Step | Expected |
|---|---|---|
| 5.1 | Open the list | Shows DC No., Customer/Vehicle (+insurer), Source badge, Docs count, Status badge, Created; sortable columns. |
| 5.2 | Search by DC no / policy / customer / reg | Filters correctly. |
| 5.3 | Filter by **Status** and by **Source** (Customer / Insurance Claim) | List narrows. |
| 5.4 | **Open** a row → edit; **delete** a row (confirm modal) | Edit opens the tabbed editor; delete removes the collection + its items/verifications (cascade). |

---

## 6. Permissions / regression

| # | Step | Expected |
|---|---|---|
| 6.1 | User without `document_collection.view` | Menu item hidden; `/document-collections` → 403. |
| 6.2 | User without `document_collection.create` / `.delete` | New button hidden / delete blocked (403). |
| 6.3 | Job Card, Job History, masters | Unchanged (regression). |

---

## Automated coverage (already green)
- `php artisan test --filter='DocumentCollection'` → 7 passed (index, auth, create+DC number+redirect, checklist snapshot, items with status/attachment/rejection, verification, delete).
- The 5 masters → 50 passed.
- Full suite re-run after the build (count in chat).

---

## After testing — feedback to capture
1. Is the 3-tab split (Details / Documents / Verification & Sign-off) right, or should Documents lead?
2. Should setting all required docs to **Received** auto-advance status to **Received** (vs manual)?
3. **Integration** (designed, not yet wired): completing a Document Collection should advance the linked Job Card's **insurance stage** (Document Collection → Claim Intimation). Build next?
4. **Acknowledgment slip PDF** + **Document Collection Report** (CSV report column) — build as sub-phase B.3?
5. Downstream insurance modules the stage tree points at — **Claim Intimation** (row 20), **Surveyor Inspection** (row 21) — next?
