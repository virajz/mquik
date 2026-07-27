# Internal Parts Inquiry (IPI) — module 14

An advisor asks the store for a part's **rate, availability and brand**. Extends the earlier
thin `InternalPartsInquiry` stub (Index-only, 4-state status) into the full row-14 spec.

## What's done

**Extended transactional module** `app/Modules/InternalPartsInquiry/`
- Parent `InternalPartsInquiry` (`internal_parts_inquiries`), auto-numbered `IPI-#####`.
  - Added: department, customer, customer vehicle, supplier/vendor, priority, inquiry type
    (Against Job Card / Stock Replenishment / Special Order / Emergency), approval authority
    (Service Advisor / Store Manager / Workshop Manager / Owner / Admin), TAT
    (Immediate / Same Day / Next Day / 2–3 Days / Custom + custom days), rejection reason.
  - `job_card_id` relaxed to nullable (stock-replenishment inquiries have no job card).
  - Status vocabulary widened to the 9-state set: Pending, In Progress, Partially Available,
    Fully Available, Not Available, Alternative Suggested, Ordered, Completed, Cancelled
    (old open/responded/closed rows remapped on migrate).
- Child `InternalPartsInquiryItem` (parts lines) — added spare brand, part type (Genuine /
  Aftermarket / OEM / Refurbished via **PartTypeMaster** = the "Inventory Type"), UOM, HSN, tax,
  vehicle variant (applicability), rate before tax, stock status (Available / Not Available /
  Reserved / Issued / Ordered / In Transit / Backorder), alternative option (Primary / Alternate
  Part / Alternate Brand). **Picking a spare auto-fills brand/part-type/UOM/HSN/tax/rate.**
- New child `InternalPartsInquiryAttachment` — typed evidence photos (Before/After/Damage/Fault
  via PhotoTypeMaster) or PDF/image documents.
- Dedicated create/edit **page** (was modal-less stub); Index gained a 4-tile dashboard
  (Open / Pending / Ordered / Cancelled-N/A), status + type + requester filters, Edit links.

**New master** `IpiRejectionReasonMaster` (`ipi_rejection_reasons`) — Not in Stock, Obsolete Part,
Wrong Part Requested, Budget Issue. Full CRUD + import/export, seeded, menu under **Inventory**.

**Report** `IpiReport` (`/ipi-report`) — filterable (status/type/requester/date) read-only
screen + CSV export.

Tests: IPI 16, rejection-reason master 10, report 6 — all green.

## How to visually test on the UI

1. **Inventory → Internal Parts Inquiry** → **New Inquiry**.
2. Pick **Inquiry Type** = *Stock Replenishment* (note: no job card needed), set **Priority** and
   **Requested By**; optionally an **Approval Authority**.
3. Under **Vehicle & Supplier**, search-pick a **Vendor**, **Customer**, **Vehicle**; optionally a
   **Job Card**.
4. **Parts Requested** → search-pick a **Spare** → confirm Brand / Inventory Type / UOM / Tax / Rate
   auto-fill. Set **Qty**, **Stock Status** (e.g. *Reserved*), **Option** (*Primary/Alternate*).
   Add a second part line; the description is required per line.
5. **Timing**: set TAT = *Custom* → confirm the custom-days field reveals.
6. **Photos & Attachments**: Add file → upload an image or PDF, pick an evidence type.
7. **Status**: set to *Not Available* → confirm the **Rejection Reason** field reveals and is
   required on save.
8. Save → back on the index with an `IPI-#####` row. Dashboard tiles are clickable status filters.
9. **Inventory → IPI Report** → filter and **Export CSV**.

## Related modules impacted

- **Reused (no changes):** JobCard, WorkshopDepartmentMaster, EmployeeMaster, CustomerMaster,
  CustomerVehicleMaster, VendorMaster, SpareMaster, SpareBrandMaster, UnitOfMeasureMaster,
  HsnMaster, TaxMaster, PriorityMaster, PhotoTypeMaster, VehicleVariantMaster, and **PartTypeMaster**
  (reused as Inventory Type — no new Genuine/Aftermarket master invented).
- **DatabaseSeeder:** registered `IpiRejectionReasonMasterSeeder`.
- **Schema:** additive columns on `internal_parts_inquiries` + `internal_parts_inquiry_items`, new
  `internal_parts_inquiry_attachments` table, `job_card_id` made nullable, `status` widened + remapped.
- Permissions registered via `php artisan auth:sync-permissions`.

## Effect on the system

Turns the parts-desk conversation into a tracked, searchable record linking a job card + vehicle
to a supplier, with per-line rate/availability/brand and a 9-state lifecycle. The spare picker
pulls commercial defaults straight from the catalogue, so the store answers faster and the data
stays consistent with SpareMaster. Additive — no existing screen changed behaviour; the previously
inert IPI stub is now a working create/edit workflow with a report.

## Design note

The line-level "Vehicle – Brand/Model/Variant/Transmission/Fuel" applicability is captured with a
single `vehicle_variant_id` FK per part (a variant already encodes brand→model→transmission→fuel),
matching how SpareMaster models compatibility — rather than five separate columns.
