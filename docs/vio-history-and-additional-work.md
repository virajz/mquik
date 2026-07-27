# VIO gaps — Additional Work flag + VIO History report

Two additions completing requirement row 9 (Vehicle Inspection Order).

## What's done

**1. "Additional Work Performed" flag**
- Added `is_additional` (boolean) to `vehicle_inspection_order_scopes`.
- A work-scope line can now be marked as work discovered *during* inspection, beyond the
  originally-booked scope. Surfaced as a checkbox on each Work Scope row in the VIO editor.

**2. VIO History report** — `app/Modules/InspectionOrderHistory/` (`/inspection-order-history`)
- Read-only, filterable log of all inspection orders: search (VIO no / job card),
  technician / bay / status filters, created-date range.
- Columns: VIO no, job card, vehicle reg, technician, advisor, bay, priority, status, TAT
  (started→ended). CSV export.
- Menu under **Inspection**.

Tests: VIO suite 15, VIO History 7 — all green.

## How to visually test on the UI

1. **Inspection → Vehicle Inspection Orders** → open/create an order → **Work Scope** tab →
   add a scope line → tick **Additional work performed** → save → reopen and confirm it persists.
2. **Inspection → VIO History** → filter by technician/bay/status or a date range →
   confirm rows narrow → **Export CSV** downloads the filtered set.

## Related modules impacted

- **Reused (no changes):** VehicleInspectionOrder (model/relations), JobCard, CustomerVehicleMaster,
  EmployeeMaster, BayMaster, PriorityMaster.
- New permissions `inspection_order_history.view` / `.export` synced.

## Effect on the system

Reporting/analytics separate their concern from the operational editor: the VIO editor stays for
data entry, while VIO History is a fast read-only view over the same data with turnaround visibility.
The `is_additional` flag lets downstream reports distinguish original vs additional labour. Additive —
no existing screens changed behaviour.
