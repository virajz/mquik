# Invoice Correction Request / Response — module 59

The service advisor requests a correction to an already-raised invoice (billing name / GST / address /
vehicle / qty / rate / add-remove line / discount / tax etc.); admin approves, and the invoice is
corrected in place, credit-noted-and-reissued, or cancelled-and-reissued. Header + item-wise correction
lines (each carrying the **old vs new** value) + document attachments.

## What's done

`app/Modules/InvoiceCorrection/` (`INC-#####`, group **Sales**, route `invoice-correction.index`)
- Parent `invoice_corrections` + `invoice_correction_items` + `invoice_correction_attachments`.
- Enums: `requestTypes` (the full 20-value list — billing-name B2C/B2B swaps, GST, address, vehicle,
  qty/rate, add/delete labour or spares, discount, tax, insurance share, recommendation),
  `correctionReasons`, `priorities`, `billingActions` (correct existing / credit-note & reissue / cancel
  & reissue), `invoiceTypes` (regular / insurance / counter_sales / qcare), `statuses` (requested /
  under_review / on_hold / approved / corrected / rejected), `rejectionReasons`. Item `itemTypes`.
- **Per line:** item type (spare/labour), spare (auto-fills uom/hsn/tax/description), **old value / new
  value**, other note, qty, rate — lines are optional (header-only corrections like an address fix save
  with no lines).
- **Timestamps** stamped automatically: `requested_at` on create; `approved_at` on approve/correct;
  `corrected_at` when the status becomes *Corrected*. Invoice ref upper-cased on save.
- **Index**: KPIs (Pending / Approved / Rejected / Corrected), CSV **Invoice Correction Report**.
- Tests: **10 green**.

## How to visually test on the UI

1. **Sales → Invoice Correction → New Correction.** Pick a **Correction Type** and reason, a **Billing
   Action**, the **Invoice Type**, priority, and the invoice reference; set advisor + "mistake by".
2. Add correction lines — pick a spare (auto-fills), enter the **Old Value** and **New Value**; or save
   a header-only correction (e.g. Address) with no lines.
3. Move **Status** to *Approved* → *Corrected* (stamps approved/corrected timestamps); *Rejected* reveals
   the rejection reason. Attach the original / revised invoice copy or GST certificate; save.
4. Index KPIs show pending/approved/rejected/corrected; **Invoice Correction Report** exports the CSV.

## Related modules impacted

- **Reused (no changes):** JobCard, Customer + Vehicle, InsuranceCompanyMaster, WorkshopDepartment,
  ServiceType, EmployeeMaster (advisor / mistake-by), SpareMaster + uom/hsn/tax masters.
- New permissions synced; menu under **Sales**.

## Effect on the system

Adds a governed, audited edit path for raised invoices with old→new value capture. Additive — no
existing module changed.

## Design note (interpretation — flag)

The spec's **system-automation rules** (only the advisor may initiate; invoice editing blocked until
admin approval; approved requests auto-route to an editors' dashboard; GST-impact corrections trigger
compliance validation) are **not wired as enforced behaviour** — the module records the request,
approval and old→new audit trail (all corrected values are stored on the lines), but the edit-lock,
role-gating and GST-compliance hooks are follow-ups to wire against the invoice modules. The
`invoice_reference` is free-text (covers all four invoice types) rather than a typed FK.
