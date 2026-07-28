# Document Delivery — module 22

Track outbound document movement to the customer / insurer — who delivered what, by which mode, the
acknowledgement, and delivery status — with a per-document checklist. The **outbound** counterpart of
the existing `DocumentCollection` (inbound) module.

## What's done

**New module** `app/Modules/DocumentDelivery/` (`DD-#####`).
- Parent `document_deliveries`: job card, customer, vehicle, insurer + delivery **state/city/area**;
  **advisor** (created by) + **driver** (delivered by) employees; **recipient type** (Owner Self /
  On Behalf); **courier company** (reused CourierCompanyMaster); **delivery mode** (Hand to Hand /
  Porter / Courier — courier/porter reveals the courier picker); **acknowledgement type** (Physical /
  Digital Signature / OTP / Email); **status** (Pending / Delivered / In Transit / Returned / Re-sent);
  **delivery-failure reason** + **missing-document reason** (reused MissingDocumentReasonMaster —
  revealed on Returned/Re-sent); **follow-up mode** + reminder frequency (+ custom days); `delivered_at`
  (required + revealed when Delivered).
- Checklist child `document_delivery_items` — a document name + **delivered** tick per row, pre-seeded
  with the standard set (RC Book, Insurance Policy, DL, Aadhar, PAN, PUC) via a datalist quick-add.
- Index with 4-tile dashboard (Pending / In Transit / Delivered / Returned-Resent), search, status +
  mode + insurer filters, and the **Document Delivery Report** (CSV export).

Tests: 12 green.

## How to visually test on the UI

1. **Insurance → Document Delivery → New Delivery.**
2. **Recipient**: link Job Card + Vehicle, pick Customer, **Recipient** = *Owner Self*, optionally an
   insurer + state/city/area.
3. **Delivery**: pick advisor + driver; set **Mode** = *Courier* → confirm the **Transport/Courier**
   picker reveals; set **Acknowledgement** = *OTP Verification*.
4. **Document Checklist**: the standard docs pre-fill; tick **Delivered** on the ones handed over;
   add a custom document (datalist suggests the standard names). Blank rows are dropped on save.
5. **Status**: set *Delivered* → confirm **Delivered At** reveals and is required; set *Returned* →
   confirm **Failure Reason** + **Missing Document Reason** reveal. Set reminder = *Custom* → days
   field reveals.
6. Save → back on index with a `DD-#####` row; tiles filter by status; **Report** exports the CSV.

## Related modules impacted

- **Reused (no changes):** JobCard, CustomerMaster, CustomerVehicleMaster, InsuranceCompanyMaster,
  EmployeeMaster, CourierCompanyMaster, MissingDocumentReasonMaster, FollowUpModeMaster.
- New permissions synced; menu under **Insurance**. Reminders are config-only.

## Effect on the system

Completes the document lifecycle: `DocumentCollection` handles inbound (collecting docs from the
customer), and Document Delivery handles outbound (handing docs to customer/insurer) with signature/
OTP acknowledgement and a courier trail. Additive — DocumentCollection is untouched.

## Design notes

- **Build-new, not extend DocumentCollection:** it's the opposite direction with a distinct field set
  (courier, recipient, delivery mode, acknowledgement, delivery status). Overloading the collection
  table with a direction flag would strain it; masters are reused instead.
- Delivery mode / acknowledgement / status / failure reason / recipient are model enums; the checklist
  is a free-text child seeded from a standard-documents list. Insurer address is free-text (state/city/
  area) rather than coupling to region/location masters.
