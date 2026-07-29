# Consumable Approval Request / Response — module 50

The floor requests approval to book a consumable / labour **loss or damage** against a job card (paint,
VA, workshop consumables, or a labour loss); the store manager / owner approves, holds or rejects.
Header (request) + item-wise loss lines (each with the loss/damage type, purchase / outside-labour ref
and a damaged photo) + an approval-screenshot attachment.

## What's done

`app/Modules/ConsumableApproval/` (`CA-#####`, group **Inventory**, route `consumable-approval.index`)
- Parent `consumable_approvals` + `consumable_approval_items` + `consumable_approval_attachments`.
- **Per line:** item type (spare/labour — captures labour loss too), spare (auto-fills uom/hsn/tax/
  rate), uom / hsn / tax, department, **loss/damage type** (the full 14-value list incl. date expired,
  leakage, evaporation, trial/testing, theft, goodwill, one-side warranty), a **Purchase Ref** (VPO)
  and **Outside Labour Ref** (OLO), qty, rate, and a **damaged photo**.
- Enums: `consumableCategories` (paint / va / workshop / other), `priorities` (normal / medium / high),
  `statuses` (requested / on_hold / under_review / approved / rejected), `approvalResponses`
  (justification_required / other), `lossDamageTypes`, item `itemTypes`. Attachment: approval screenshot.
- **Timestamps** are stamped automatically: `requested_at` on create; `approved_at` / `rejected_at`
  the first time the status becomes approved / rejected (on create or a later edit).
- **Index**: KPIs (Pending / Approved / Rejected / **Expired Material Issued** / **Damaged Material
  Issued**) plus Paint / VA / this-month **consumption value** cards and a **department-wise
  consumption** panel; CSV **Consumable Approval Report**.
- Tests: **10 green**.

## How to visually test on the UI

1. **Inventory → Consumable Approval → New Request.** Pick a **Job Card**, **Consumable Category** and
   department; set **Priority** and the **Approval Authority**.
2. Add loss lines — flip **Type** between Spare and Labour, pick a spare (auto-fills), set the
   **Loss/Damage Type** (e.g. Evaporation Loss), qty/rate, and upload a **damaged photo**; optionally a
   Purchase / Outside-Labour reference.
3. Set **Approval Status = Approved** — the approved timestamp is stamped; attach an approval
   screenshot; save.
4. Index shows pending/approved/rejected + expired/damaged counts, paint/VA/monthly consumption value,
   a department-wise breakdown, and the **Consumable Approval Report** CSV.

## Related modules impacted

- **Reused (no changes):** JobCard, WorkshopDepartment, EmployeeMaster, FollowUpModeMaster,
  VendorPurchaseOrder (38, purchase ref), OutsideLabourOrder (29, outside-labour ref), SpareMaster +
  uom / hsn / tax masters.
- New permissions synced; menu under **Inventory**.

## Effect on the system

Adds a governed loss-and-damage approval trail for consumables and labour loss — feeding consumption
analytics (paint / VA / department-wise / monthly). Additive — no existing module changed.

## Design note (interpretation — flag)

The spec listed "Purchase Reference (Add button)" and "Outside Labour Reference (Add button)" — I placed
both as **per-line** references (one purchase + one outside-labour ref per loss line) rather than a
header-level repeatable list. The report column was blank, so a CSV **Consumable Approval Report** export
was added for consistency with sibling modules. Say the word if you'd prefer header-level multi-refs or
no export.
