# Stock Counting (Physical & System Stock Verification) — module 83

A physical stock-count session: per-spare system-vs-physical quantity with computed variance, mismatch
reasons and condition, purchase reference, and count-sheet / approval attachments. Header + count lines +
attachments. Doc series `SC-#####`.

## What's done

`app/Modules/StockCounting/` (`SC-#####`, group **Inventory**, route `stock-counting.index`)
- Parent `stock_counts` + `stock_count_items` + `stock_count_attachments`.
- Auto **count_no** = `SC-#####` (5-digit id), stamped in booted() created hook.
- Header: `count_start_date` / `count_end_date`, `counting_method` (QR-Barcode / RFID / Manual),
  `verification_status` (pending / in_progress / completed / cancelled), storage location (→ `racks`),
  inventory group (→ `inventory_groups`), team leader (→ `employees`), team name + members (free text),
  notes/remarks.
- **Count lines** (`stock_count_items`): spare picker (auto-fills uom / tax / description — see design note),
  barcode, `system_stock`, `physical_stock`, **`diff_qty` = physical − system (computed in `syncItems`)**,
  net_total, `mismatch_reason` (16-value variance list), `spares_condition` (broken / rusted / expired /
  water-damage / transit-damage / manufacturing-defect), purchase date / invoice no / vendor name, entry
  time, remark.
- **"Same items must not be repeated"** — save() rejects duplicate `spare_id` across rows with a per-row error.
- Attachments: physical count sheet / management approval / damage photo.
- **Index KPIs**: In Progress / Completed Today / **Total Mismatch Qty** (Σ|diff_qty| over non-cancelled
  counts) / Pending. CSV **Stock counting report**.
- Tests: **9 green** (stable over 4 runs; covers computed diff_qty, duplicate rejection, KPIs, CSV).

## How to visually test on the UI

1. **Inventory → Stock Counting → New Count.** Set start/end date, **Counting Method**, storage location,
   inventory group, team leader / name / members.
2. **Add item** → pick a spare (auto-fills uom/tax/description), enter **system** and **physical** stock →
   the row shows the variance; set a **Mismatch Reason** and **Condition** if they differ. Try adding the same
   spare twice → it's rejected on save.
3. Set **Verification Status**, attach the count sheet; save (number `SC-#####`). Index shows the KPIs; the
   report exports CSV.

## Related modules impacted

- **Reused (no changes):** SpareMaster, RackMaster (`racks`), InventoryGroupMaster (`inventory_groups`),
  EmployeeMaster, uom/tax masters.
- New permissions synced; menu under **Inventory**.
- **Feeds Stock Mismatch Approval (83)** — a completed count with variance is what a mismatch-approval request
  references (`stock_count_id`).

## Effect on the system

Adds the physical-verification record and the variance data that drives the mismatch-approval / adjustment
flow. Additive — no existing module changed. Note: this **records** variance; it does **not** post inventory
adjustments — that happens after Stock Mismatch Approval.

## Design note (interpretation — flag)

`system_stock` is **manual entry** — SpareMaster has no on-hand/system-stock column (only rate/mrp/min/max
qty), so it can't be auto-fetched; wire it to a live stock ledger when one exists. Spares also carry `hsn_code`
as a string (no `hsn_id`), so HSN isn't auto-filled. The automation rules (auto barcode/RFID scan ingestion,
auto system-stock pull, auto variance analytics) are not wired — the module is the count + variance data model.
