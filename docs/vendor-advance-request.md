# Vendor Advance Payment Request / Response — module 36

Store/accounts request for an **advance to a vendor** for special orders, with a document checklist and
approval (hold / reject) handling. The money-out mirror of module 25 (Customer Advance Receipt Request);
document-checklist pattern borrowed from module 22 (Document Delivery).

## What's done

`app/Modules/VendorAdvanceRequest/` (`VAR-#####`, group **Finance**, route `vendor-advance-request.index`)
- Parent `vendor_advance_requests` + child `vendor_advance_request_documents` (checklist) +
  `vendor_advance_request_attachments`.
- Links vendor / VPI / VPO-approval / job card / customer / vehicle / dept / service type / bank /
  priority / follow-up mode, and prepared/verified/advisor employees.
- Enums: `statuses` (requested / in_progress / partially_paid / fully_paid / failed / on_hold /
  rejected / cancelled), `partsCategories`, `advanceReasons`, `paymentModes`, `vendorCategories`,
  `holdReasons`, `rejectionReasons`, `reminderFrequencies`. `standardDocuments` pre-seeds the checklist
  (Quote/Proforma, Approval Note, Vendor Bank Details).
- **Reveal logic:** hold reason on `on_hold`, rejection reason on `rejected`, custom days when reminder
  frequency is `custom`. Reminders are **config-only** (never sent), per policy.
- **Index**: KPIs (Pending / Paid / Rejected), CSV **Payment Report**.
- **Edit**: pre-seeded document checklist child + attachments child.
- Tests: **12 green**.

## How to visually test on the UI

1. **Finance → Vendor Advance Request → New Request.** Pick a **Vendor** (required), an **advance
   reason**, **amount** and **payment mode**; the **document checklist** is pre-seeded — tick what's on
   file, add rows as needed.
2. Set status to **On Hold** → hold reason reveals; **Rejected** → rejection reason reveals.
3. Set **Reminder Frequency = Custom** → "every N days" reveals (stored only, nothing is sent).
4. Save; row shows a status badge. Index → **Payment Report** exports the CSV; KPI cards filter.

## Related modules impacted

- **Reused (no changes):** VendorMaster, VendorPurchaseInquiry (17), VpoApproval (35), JobCard,
  Customer/Vehicle, BankMaster, PriorityMaster, FollowUpModeMaster, EmployeeMaster.
- New permissions synced; menu under **Finance**.

## Effect on the system

Starts the vendor-advance chain: a governed **request** that feeds the actual **Advance Payment Entry**
(37). Additive — no existing module changed.
