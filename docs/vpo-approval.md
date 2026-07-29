# VPO (Vendor Purchase Order) Approval / Response — module 35

Admin approval for a vendor purchase order, with **per-line** approval (part, qty, rate, discount,
TAT) and last-purchase context. Reshaped from module 23 (Sales Estimate Approval) + module 17 (VPI):
header + per-line approval items + charges + attachments.

## What's done

`app/Modules/VpoApproval/` (`VPA-#####`, group **Purchase**, route `vpo-approval.index`)
- Parent `vpo_approvals` + `vpo_approval_items` (per-line: `part_approved`, `qty_approved`,
  `rate_approved`, `discount_approved`, `tat_approved`, `last_purchase_price/vendor/date`) +
  `vpo_approval_charges` + `vpo_approval_attachments`.
- Enums: `poApprovalTypes`, `approvalLevels` (l1_store / l2_accounts / l3_owner), `paymentTerms`,
  `vendorCategories`, `rejectionReasons`, `statuses`. Attachment types: vendor_quotation /
  quote_comparison / approval_notes.
- **Validation:** `job_card_id` required when the PO type is odd_item / high_value; `vendor_id`
  required; `approved_at` stamped when fully approved.
- **Index**: KPIs (Pending / Partially Approved / Approved / Rejected), CSV **Purchase Report**.
- **Edit**: items / charges / attachments repeaters, per-line approval, `spareOptions($index)`
  per-line searched picker.
- Tests: **13 green**.

## How to visually test on the UI

1. **Purchase → VPO Approval → New Approval.** Set **PO Approval Type** (choose odd_item/high_value
   to force a **Job Card**), pick a **Vendor**.
2. Add line items; set qty/rate/discount **approved** per line and tick **Part Approved**; add a
   charge and a vendor-quotation attachment.
3. Set status to **Fully Approved** — `approved_at` is stamped. Try **Rejected** to reveal the reason.
4. Index → **Purchase Report** exports the CSV; KPI cards filter.

## Related modules impacted

- **Reused (no changes):** VendorMaster, JobCard, SpareMaster, ChargeTypeMaster, the approval-mode
  master, part/tax masters.
- New permissions synced; menu under **Purchase**.

## Effect on the system

Adds the governance gate between a purchase inquiry (VPI, 17) and the actual order (VPO, 38): a PO can
be approved line-by-line before it's raised. Additive — no existing module changed.
