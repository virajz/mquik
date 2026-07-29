# Excess Stock Approval Request / Response — module 60

The store requests approval to return or write off excess / dead stock (wrong or excess purchase,
cancelled order, non-returnable, expired warranty / return window, open packing, etc.); admin approves
or rejects. Header + item-wise spare lines (qty × rate = excess value) + document attachments.

## What's done

`app/Modules/ExcessStockApproval/` (`ESA-#####`, group **Inventory**, route `excess-stock-approval.index`)
- Parent `excess_stock_approvals` + `excess_stock_approval_items` + `excess_stock_approval_attachments`.
- Enums: `excessStockReasons` (the 11-value list), `vendorRejectionReasons` (fitment issue / damaged at
  workshop), `priorities`, `statuses` (requested / on_hold / under_review / verbal_clarification /
  approved / rejected). Attachment types: purchase invoice copy / damaged proof / vendor rejection proof.
- **References:** the GRN (45), the technician parts-return handover (46), and a free-text purchase
  invoice reference; store advisor / in-charge / executive + "mistake by" employees.
- **Reveal / conditional:** vendor rejection reason is required when the reason is *Vendor Return
  Rejected*. Spare pick auto-fills uom/hsn/tax/rate.
- **Timestamps** stamped automatically: `requested_at` on create; `approved_at` / `rejected_at` the
  first time the status becomes approved / rejected.
- **Index**: KPIs (Approval Pending / Accepted / Rejected) plus **Excess Stock Value** (all lines) and
  **Dead Stock Value** (lines whose reason is non-returnable / vendor-rejected / return-window or
  warranty expired / open-packing / vehicle-scrapped — shown red); CSV **Excess Stock Report**.
- Tests: **10 green**.

## How to visually test on the UI

1. **Inventory → Excess Stock Approval → New Request.** Pick the **Excess Stock Reason** (choose *Vendor
   Return Rejected* to reveal the required vendor-rejection reason), set priority, and link a **GRN** /
   **Technician Parts Return** / purchase invoice ref.
2. Add spare lines — pick a spare (auto-fills), set qty and rate (qty × rate is the excess value).
3. Set **Approval Status = Approved**; attach a purchase-invoice / damaged / vendor-rejection proof; save.
4. Index shows pending/accepted/rejected counts plus **Excess Stock Value** and **Dead Stock Value**
   (red); **Excess Stock Report** exports the CSV.

## Related modules impacted

- **Reused (no changes):** GoodsReceipt (45), GoodsHandover (46), WorkshopDepartment, ServiceType,
  FollowUpModeMaster, EmployeeMaster, SpareMaster + uom/hsn/tax masters.
- New permissions synced; menu under **Inventory**.

## Effect on the system

Adds a governed return / write-off approval for excess and dead stock, with excess- and dead-stock value
visibility. Additive — no existing module changed.

## Design note (interpretation — flag)

**Dead Stock Value** is derived by treating a fixed set of reasons as "dead" (non-returnable,
vendor-return-rejected, return-window-expired, warranty-expired, open-packing, vehicle-scrapped) and
summing those lines' qty × rate; **Excess Stock Value** is the sum across all request lines. If you'd
rather dead-stock be a separate flag per line (or scoped to approved requests only), that's a small
tweak — say the word.
