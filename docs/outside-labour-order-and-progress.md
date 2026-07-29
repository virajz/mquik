# OLO (Outside Labour Order) + OLR (Outside Labour Progress) — modules 29 & 30

The finalized outside-vendor work **order** (OLO) and the progress/hours **response** report (OLR).
Like FWO/FWR, OLO is a full work-order (time tracking / pauses / findings / photos / item results),
so it was **cloned from `FinalWorkOrder`** and layered with the outside-labour fields (vendor, OLI
reference, order type, communication/follow-up).

## What's done

### 29 — Outside Labour Order  `app/Modules/OutsideLabourOrder/`  (`OLO-#####`)
Cloned from FinalWorkOrder (parent + Items[+hours] + Scopes + Photos + Pauses + Findings + time
helpers `grossMinutes`/`pauseMinutes`/`netTatMinutes`/`totalItemHours`), then added:
- **Vendor / Contractor** (VendorMaster), **Outside Labour Inquiry** reference (module 12),
  **Order Type** (reused ServiceSpecialistMaster — Denting/Painting/Upholstery/Repowering/Alloy-Rim/
  Electrical/Accessories-PPF/Lathe), **Customer Vehicle**, **Sequence Ordering**, **Communication
  Mode** (WhatsApp/Email/Phone), **Follow-up Mode** (FollowUpModeMaster).
- Inherited: department, service type, advisor + technician, **bay** (needed for contractors like
  denting/painting, N/A for outside vendors), work scopes (complaint/job-desc/package + additional-
  work flag), priority, completion type, inspection template, time tracking (assigned/accepted/start/
  end + pause/resume), item results (IA/FA/OK) + **hours**, before/after item photos + additional
  evidence (front/rear/damage/fault/other), pause/rework/delay reasons, 6-state status, dashboard.
- **Outside Labour Status Report** = the Index CSV export.

### 30 — Outside Labour Progress (OLR)  `app/Modules/OutsideLabourProgress/`  (`outside-labour-progress.index`)
Read-only report tracking **progress, completion and total hours** per outside labour order — vendor,
order type, item hours, net TAT, status, completion type. Filters by status / vendor / date; CSV
export ("Status Report"). This is the OLR deliverable.

**Reuse links:** `technician_findings` gained `outside_labour_order_id` (so Additional Findings serve
VIO, FWO **and** OLO); the OLO tables carry `priority_id` + item `hours` (from the FinalWorkOrder
clone).

Tests: OLO 18, OLR 6 — all green.

## How to visually test on the UI

1. **Workshop → Outside Labour Orders → New Work Order.** On the **Details** tab pick a **Vendor**,
   **Order Type** (e.g. PPF/Denting), **Inquiry Ref**, **Vehicle**, **Sequence**, **Communication**
   + **Follow-up** modes, and a **Bay** (for contractor work). Add work scopes; move status through
   Assigned → WIP → Completed (stamps times); log item hours + a pause row.
2. Index → **Outside Labour Status Report** exports the per-order CSV.
3. **Workshop → Outside Labour Progress:** filter by vendor / status / date; each row shows item
   hours + net TAT + status; **Status Report** exports the CSV.

## Related modules impacted

- **Reused (no changes):** VendorMaster, OutsideLabourInquiry (12), ServiceSpecialistMaster, JobCard,
  CustomerVehicleMaster, FollowUpModeMaster, WorkshopDepartment/ServiceType/Employee/Bay,
  InspectionTemplate, Complaint/JobDescription/ServicePackage, PhotoType, Priority, hold/rework/delay
  reason masters, JobHistory recorder.
- **Extended:** `technician_findings` (+ `outside_labour_order_id`) + relation.
- New permissions synced; both menus under **Workshop**.

## Effect on the system

Completes the outside-labour chain: **OLI (inquiry, 12) → OLO (order, 29) → OLR (progress, 30)** — the
order finalises a vendor for outside work and tracks its execution/time, and the progress report gives
completion/hours visibility per vendor. Additive; VIO/FWO untouched.

## Design note (interpretation — flag)

As with FWO/FWR, the spec gives OLO (29) and OLR (30) the **same field list**, with OLR's purpose being
"track progress, completion, total hours." Rather than a second heavyweight data-entry module, **OLR is
the progress/hours captured on the OLO record + a dedicated progress report**. Both 29 and 30 name their
report "Outside Labour Status Report"; the OLR report route is `outside-labour-progress` to avoid
colliding with the existing `outside-labour-status-report` module (which reports over OLI inquiries).
If you want OLR as a separate response *document*, that's a follow-up.
