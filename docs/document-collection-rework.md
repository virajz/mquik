# Document Collection — rework tracker

Audited: the module already carries dept/service-type fields, reminder frequency
(daily / every 2 days / custom + days), checklist + verification templates, and
per-item files. The gaps are job-first selection, the follow-up log, per-document
receive stamps, derived status, and the history filters.

Legend: `[ ]` pending · `[x]` done · `[~]` resolved by design (see note)

## Already in place (verified)
- [x] Dept & Service Type fields exist on the record
- [x] Auto Reminder Frequency — Daily / Every 2 Days / Custom (+ custom days) already modelled
- [x] Easy search groundwork — shared `Searchable` handles space/% and nested relations

## Chunk 1 — Customer & Vehicle, job-first
- [x] 1. Job No. picker, format "JC-92100 — SWIFT — GJ 05 RD 1234"; searchable, job-first (today it demands the vehicle first and shows bare numbers)
- [x] 2. Auto-fill customer & vehicle (and dept/service/advisor) from the picked job
- [x] 3. Dept → Service Type follow the house convention (dept-scoped options)
- [x] 4. Quick-add (+) for Customer and Customer Vehicle

## Chunk 2 — Stamps & status
- [x] 5. Requested Date & Time — disabled, stamped on create
- [x] 6. Received Date & Time removed — each document stamps its own `received_at` when its status flips to Received
- [x] 7. Status auto-derived: pending → requested → received (all required docs in); rejected/cancelled stay deliberate

## Chunk 3 — Follow-up & request message
- [x] 8. Follow-up log: multiple follow-up dates, followed-up by, customer response (new child table + repeater)
- [x] 9. Request Message link — composed list of pending documents, copy + WhatsApp link to the customer
- [x] 10. Mandatory: Customer, Vehicle, Request Source, Purpose, Dept, Service Type, Advisor (created by), Document Checklist, Verification Checklist

## Chunk 4 — History
- [x] 11. Default to Pending collections on load
- [x] 12. New filters: date From/To, Department, Created By, Collected By; Job Card No. + Policy No. searchable
- [x] 13. Columns: Requested / Received dates, Vehicle, Job No., Dept, Created By, Collected By, Requested Time
- [x] 14. Status column derives (from item 7); sort on all column headings (subquery sorts for names)

## Notes
- "From Date, To Date" in the result columns read as the Requested / Received dates — flagged if that meant something else.
- Job no. format "MQ/JC/26-27/1521" is the client's legacy numbering; this system's `JC-#####` is shown instead, with the same vehicle/reg suffix.
