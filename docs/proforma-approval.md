# Proforma Approval Request / Response — module 55

The staged digital approval of a proforma before it is converted to an invoice: Billing Executive
prepares → Store In-charge / Service Advisor approve → Admin approves → convert to invoice. Header
(the proforma being approved, timestamped per stage) + per-role checkpoint lines + document
attachments.

## What's done

`app/Modules/ProformaApproval/` (`PFA-#####`, group **Sales**, route `proforma-approval.index`)
- Parent `proforma_approvals` + `proforma_approval_checkpoints` + `proforma_approval_attachments`.
- Enums: `stages` (6 stages), `approvalAuthorities`, `priorities` (normal/high/urgent), `statuses`
  (under_preparation / advisor_approval_pending / store_approval_pending / admin_approval_pending /
  return_for_correction / on_hold / converted_to_invoice), `lossReasons`, `missingReasons`,
  `discountTypes`, `rejectionReasons`.
- **Per-role checkpoints:** each checkpoint line picks a **role** (billing / store / advisor / admin)
  and a **checkpoint** from that role's list — the full Billing / Store / Advisor / Admin check-point
  sets from the spec (IPO/FWO mismatches, MRP, group/brand/vendor-wise Sales/Purchase/P&L, estimate
  mismatch, promised-date, discount/loss/warranty amounts, etc.) — marked **OK / Flagged** with a note.
  Validation rejects a checkpoint key that isn't in any role's list.
- **Stage timestamps** stamped automatically: `requested_at` on create; `prepared_at` the first time
  status leaves *Under Preparation*; `converted_at` when it becomes *Converted to Invoice*. The
  store / advisor / admin approved timestamps are editable datetime fields.
- **Index**: KPIs (Pending Store / Advisor / Admin Approvals), CSV **Proforma Report**.
- Tests: **11 green**.

## How to visually test on the UI

1. **Sales → Proforma Approval → New Approval.** Set the **Stage**, **Approval Authority** and
   **Priority**; link a **Job Card** / **Estimate**, customer + vehicle, and the proforma amount.
2. Add **checkpoints** — pick a role (the checkpoint list changes per role), pick a checkpoint, mark it
   **OK / Flagged** and add a note.
3. Move **Approval Status** to *Store Approval Pending* (stamps prepared_at) → *Converted to Invoice*
   (stamps converted_at); *Return for Correction* reveals the rejection reason.
4. Attach the proforma print (Format-1 advisor / Format-2 admin); save. Index KPIs show the pending
   counts per approver; **Proforma Report** exports the CSV.

## Related modules impacted

- **Reused (no changes):** JobCard, SalesEstimate, InsuranceCompanyMaster, Customer + Vehicle,
  WorkshopDepartment, ServiceType, EmployeeMaster (billing / store / advisor / technician), VendorMaster,
  FollowUpModeMaster.
- New permissions synced; menu under **Sales**. Provides the `proforma_approvals` table that the
  Delivery Order module (56) references.

## Effect on the system

Adds the multi-stage billing→store/advisor→admin gate before an invoice is raised, with a checkpoint
audit trail per approver. Additive — no existing module changed.

## Design note (interpretation — flag)

The **per-stage system-automation rules** (auto-send approval link to store/advisor on prepared, to
admin on their approval, enable "Convert to Invoice" on admin approval) and the **>₹25,000 admin-only
threshold** are **not wired as automated behaviour** — links/notifications are config-only per the
project's no-send policy, and the threshold isn't enforced. The stages + statuses + timestamps model
the workflow; wiring the automation (and the amount gate) is a follow-up if you want it enforced.
