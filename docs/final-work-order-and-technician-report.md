# FWO (Final Work Order) + FWR (Technician Report) — modules 27 & 28

The official work order after estimate approval (FWO) and the technician time analytics over it (FWR).
Structurally FWO is the **VIO (module 9) shape at a later lifecycle stage**, so it was cloned from the
proven `VehicleInspectionOrder` module rather than rebuilt.

## What's done

### 27 — Final Work Order  `app/Modules/FinalWorkOrder/`  (`FWO-#####`)
Cloned from VehicleInspectionOrder (parent + Items + Scopes + Photos + Pauses), then adjusted:
- Same field set: job card, department, service type, advisor + technician, bay, work scopes
  (complaint / job-description / service-combo-AMC package, with **Additional Work** flag), work
  priority, inspection template (auto-snapshots checklist items), **time tracking** (assigned /
  accepted / started / ended + pause/resume rows), per-item result (**IA / FA / OK** + pending),
  before/after item photos, order-level evidence photos (front/rear/damage/fault), **Additional
  Findings** (Spares/Labour via TechnicianFinding), pause/rework/delay reasons, completion type,
  6-state status (Assignment Pending → Assigned → WIP → On Hold → Completed / Cancelled), dashboard
  counters.
- **Added `hours` per item** (technician's logged hours) and time helpers on the model:
  `grossMinutes()` (start→end), `pauseMinutes()` (Σ pause/resume), `netTatMinutes()` (gross − pause),
  `totalItemHours()`.
- **Work Order Analysis** = the Index's CSV export (per-order gross/pause/net-TAT/item-hours).
- Status changes still record Job Card history events (kept from the VIO clone).

### 28 — Technician Report  `app/Modules/TechnicianReport/`  (`technician-report.index`)
Read-only analytics aggregating FinalWorkOrder time **per technician**: work-order count, total item
hours, working time (gross), pause time, net TAT and average net TAT. Filters by technician, date
range, completed-only. CSV export.

**Reuse links:** `technician_findings` gained a nullable `final_work_order_id` so the same
Additional-Findings entity serves both VIO and FWO. `final_work_orders` gained `priority_id` (the VIO
FK came from a cross-cutting PriorityMaster migration the clone didn't carry).

Tests: FWO 17, Technician Report 6 — all green (incl. net-TAT math: 2h gross − 30m pause = 90m).

## How to visually test on the UI

1. **Workshop → Final Work Orders → New Work Order.** Pick job card, technician, bay; add work-scope
   lines; pick an **Inspection Template** → checklist items snapshot in. On an item set **Result** and
   **Hours**. Move **Status** to *WIP* (stamps started_at) then *Completed* (stamps ended_at); add a
   pause/resume row. Save → `FWO-#####`.
2. On the index, **Work Order Analysis** downloads the per-order time CSV.
3. **Workshop → Technician Report:** filter by technician / date; each row shows working time, pause,
   net TAT and item hours; **Export CSV**.

## Related modules impacted

- **Reused (no changes):** JobCard, WorkshopDepartmentMaster, ServiceTypeMaster, EmployeeMaster,
  BayMaster, InspectionTemplateMaster, ComplaintTypeMaster, JobDescriptionMaster,
  ServicePackageMaster, PhotoTypeMaster, PriorityMaster, WorkOrderHoldReason/Rework/DelayReason,
  JobHistory recorder.
- **Extended:** `technician_findings` (+ `final_work_order_id`), and its model gained a
  `finalWorkOrder()` relation.
- New permissions synced; both menus under **Workshop**.

## Effect on the system

FWO gives the shop the post-approval "do the work" order (distinct from the pre-work VIO inspection),
with real technician time capture; the Technician Report turns that captured time into per-technician
productivity/TAT analytics. Additive — VIO is untouched.

## Design note (interpretation — flag)

The spec lists 27 (FWO) and 28 (FWR) with the **same field list**; FWR's purpose is "capture tech
working time, item-wise hours, pause, net TAT" + a Technician Report. Rather than build a second
near-identical heavyweight data-entry module, **FWR is modelled as the technician-time layer ON the
FWO record** (technicians log start/accept/pause/resume/end + per-item hours there) **plus a
dedicated Technician Report** analytics screen. If you instead want FWR as a separate *response
document* distinct from the FWO, that's a follow-up — say the word.
