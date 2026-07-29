# Outside Labour Bill Receive & Verification — module 41

The workshop receives an outside vendor / contractor's physical invoice for outside work (denting,
painting, etc.) and **verifies** it — rate / qty / job match — before it can be paid. Header + item-wise
bill lines (each with its job card / vehicle) + invoice-photo attachments.

## What's done

`app/Modules/OutsideLabourBill/` (`OLB-#####`, group **Workshop**, route `outside-labour-bill.index`)
- Parent `outside_labour_bills` + item-wise `outside_labour_bill_items` (job card + vehicle, billed vs
  **verified amount**) + `outside_labour_bill_attachments`.
- Links the **Outside Labour Order** (29), the work-category master (reused ServiceSpecialistMaster),
  requested-by / approved-by employees, priority, follow-up mode.
- Enums: `billDocumentTypes` (tax_invoice / bill_of_supply / e_invoice / bill_book_memo),
  `workCompletionTypes`, `statuses` (requested / under_verification / on_hold / partially_verified /
  fully_verified / rejected / cancelled), `holdReasons`, `rejectionReasons`, `reminderFrequencies`,
  `vendorRatingTypes`. Attachment types: original / duplicate invoice copy, WhatsApp screenshot.
- **Reveal / conditional:** hold reason on `on_hold`, rejection reason on `rejected`, custom days when
  reminder frequency is `custom`. Reminders are **config-only** (never sent).
- **Index**: KPIs (Bill Pending to Receive / On Hold / Rejected), CSV **Outside Labour Status Report**.
- Tests: **10 green**.

## How to visually test on the UI

1. **Workshop → Outside Labour Bill Verification → Receive Bill.** Pick a **Vendor** (required), the
   **work category**, the **OL Order** reference, **bill type** + vendor bill no / date / amount.
2. Add bill lines — each with a **job card** and **vehicle**, billed rate and a **verified amount**.
3. Set **Status = On Hold** → hold reason appears; **Rejected** → rejection reason appears.
4. Attach an original / duplicate invoice photo; save. Index KPIs update; **Outside Labour Status
   Report** exports the CSV.

## Related modules impacted

- **Reused (no changes):** OutsideLabourOrder (29), VendorMaster, ServiceSpecialistMaster, JobCard,
  CustomerVehicleMaster, PriorityMaster, EmployeeMaster, FollowUpModeMaster.
- New permissions synced; menu under **Workshop**. It provides the `outside_labour_bills` table that
  the warranty-return module (43) references item-wise.

## Effect on the system

Adds the receive-and-verify gate between an outside labour order (29) and paying its vendor — no bill
is paid until rate / qty / job are checked. Additive — no existing module changed.
