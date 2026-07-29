# Customer Advance Receipt Request — module 25

Request an advance payment from the customer (typically via a link): the purpose, how the amount is
derived, payment status, reminder scheduling and follow-up.

## What's done

**New module** `app/Modules/AdvanceReceiptRequest/` (`ARR-#####`).
- Parent `advance_receipt_requests`: job card, **estimate** ref, customer, vehicle, department,
  service type, advisor/cashier employee; **advance purpose** (Odd Item PO / Urgent PO / Regular
  Parts / Outside Labour); **amount type** (Specific % of Estimate / Min / Max / Custom) — **percent**
  field reveals + required when % chosen; **amount**; **payment status** (Requested / Partially Paid /
  Fully Paid / Cancelled / Failed / Rejected / Refunded); **auto reminder** (Daily 10 AM / 2 PM / 4 PM
  / Custom — custom time reveals); **follow-up mode**; **rejection reason** (required when Rejected).
- Attachment child `advance_receipt_request_attachments` — PDF/image supporting docs.
- Index with 4-tile dashboard (Requested / Partially Paid / Fully Paid / Closed), search, status +
  purpose filters, and the **Advance Receipt Report** (CSV export).

Tests: 14 green (incl. percent/custom-time/rejection gating and attachment upload).

## How to visually test on the UI

1. **Finance → Advance Receipt Request → New Request.**
2. Link Job Card + **Estimate**, pick Customer/Vehicle/Department/Service Type/Advisor.
3. **Advance**: pick **Purpose** = *Urgent PO*; set **Amount Type** = *Specific % of Estimate* →
   confirm the **Percent** field reveals and is required; enter a **Requested Amount**.
4. **Status & Reminders**: set **Payment Status** = *Rejected* → **Rejection Reason** reveals and is
   required; set **Auto Reminder** = *Custom* → the custom **time** field reveals.
5. **Attachments**: Add file → upload a PDF/image.
6. Save → back on index with an `ARR-#####` row; tiles filter by status; **Report** exports CSV.

## Related modules impacted

- **Reused (no changes):** JobCard, SalesEstimate, CustomerMaster, CustomerVehicleMaster,
  WorkshopDepartmentMaster, ServiceTypeMaster, EmployeeMaster, FollowUpModeMaster.
- New permissions synced; menu under **Finance**. Reminders/link-sending are config-only.

## Effect on the system

Gives the desk a tracked way to ask a customer for an advance against a job/estimate, with a status
trail that flows into the actual receipt (module 26). Additive.

## Design notes

- Advance purpose / amount type / payment status / reminder time / rejection reason are model enums;
  the "Advance Receipt Report" is the Index's filtered CSV export. Sending the actual request link is
  out of scope (config-only), consistent with the project's reminder policy.
