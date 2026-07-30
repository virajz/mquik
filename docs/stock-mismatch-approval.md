# Stock Mismatch Approval (Request/Response) — module 83

The approval / adjustment flow for a stock-count variance: a request references a completed stock count,
management responds (adjust / recount / write-off …), an investigation outcome is recorded, and the request
moves through requested → under_review → approved / rejected / cancelled. Header + note attachments. Doc
series `SMA-#####`.

## What's done

`app/Modules/StockMismatchApproval/` (`SMA-#####`, group **Inventory**, route `stock-mismatch-approval.index`)
- Parent `stock_mismatch_approvals` + `stock_mismatch_approval_attachments`.
- Auto **approval_no** = `SMA-#####` (5-digit id), stamped in booted() created hook.
- **Links the Stock Count (83)** via `stock_count_id`, plus `requested_by` / `requested_to` (employees).
- Enums (static methods, TitleCase): `approvalStatuses` (requested / under_review / approved / rejected /
  cancelled), `varianceReasons` (16-value list), `managementResponses` (adjust / on_hold / recount /
  reinvestigate / write_off), `recountOutcomes` (genuine difference / system / user / vendor error / theft
  confirmed / duplicate posting / no difference), `adjustmentMethods` (FOC purchase / issue in consumption),
  `communicationModes` (WhatsApp / Email / Phone).
- **Timestamps** stamped automatically: `requested_at` on create; `approved_at` / `rejected_at` /
  `cancelled_at` first time the status reaches that state.
- **Conditional:** `management_response` required when status = *Approved*.
- Attachments: mismatch note / investigation note / approval note.
- **Index KPIs**: Requested / Under Review / Approved / Rejected. CSV **Stock Mismatch Approval Report**.
- Tests: **8 green** (stable over 4 runs).

## How to visually test on the UI

1. **Inventory → Stock Mismatch Approval → New Request.** Pick the **Stock Count** (searchable by `count_no`),
   set requested-by / requested-to, the **Variance Reason** and communication mode; save (`SMA-#####`,
   stamps requested-at).
2. Move **Status** to *Under Review*, then *Approved* — **Management Response** becomes required (stamps
   approved-at); or *Rejected* (stamps rejected-at). Record the **Recount Outcome** and **Adjustment Method**.
3. Attach an investigation / approval note. Index shows the four KPIs; the report exports CSV.

## Related modules impacted

- **Reused (no changes):** StockCounting (`stock_counts`), EmployeeMaster.
- New permissions synced; menu under **Inventory**.

## Effect on the system

Closes the stock-verification loop — **count + variance (83 Stock Counting) → approve / adjust / write-off
(this module)**. Additive — no existing module changed.

## Design note (interpretation — flag)

This module **records the decision**; it does **not post the inventory adjustment** — the actual FOC-purchase
/ consumption-issue posting that an *Adjust* / *Write-Off* implies is a cross-module inventory-ledger hook and
is not wired. The automation (auto-route by variance value/amount, auto-notify via the communication mode,
auto-recount task generation, live KPI updates) is not wired, per the no-send policy. `adjustment_method` /
`recount_outcome` are captured as data for that downstream posting step.
