# VIO — advisor page vs technician page

Tracker for the split: what the advisor sets up, what the technician records.

| # | Requirement | Status |
| --- | --- | --- |
| 1 | Before & After photo capture/upload moves to the technician page; before/after belong to the **work scope**, not the checklist; multiple photos per stage | ✅ Done |
| 2 | Evidence › Additional Work / Technician Findings — not on the advisor page, capture on the technician page | ✅ Done |
| 3 | Status & Time Tracking › Status is part of history, auto-updates | ✅ Done |
| 4 | Completion Type is part of Work Scope — item-wise, on the technician page | ✅ Done |
| 5 | Paused / Resumed date & time auto-captured on the technician page; technician picks the Reason | ✅ Done |

## What changed

**Schema** (`2026_08_31_140000_add_technician_evidence_to_vio_scopes.php`)
- `vehicle_inspection_order_scopes.completion_type` — completion is per line, not per order.
- New `vehicle_inspection_order_scope_photos` — `scope_id`, `stage` (before/after), `path`,
  `original_name`, `size_bytes`, `notes`, `sequence_no`. Many rows per stage per line.
- `vehicle_inspection_order_pauses` gains `vehicle_inspection_order_scope_id` and `paused_by_id`,
  so a pause says which line it stalled and whose it was.

**Technician Bench** (`app/Modules/TechnicianBench/Livewire/Index.php`)
- `askPauseReason()` → modal → `pause()`. The reason is required; the pause row is stamped
  `paused_at` + `paused_by_id`. `start()` closes the open pause with `resumed_at`, so every gap
  has both ends without anyone typing a time.
- `askCompletionType()` → modal → `complete()`, writing `completion_type` on that line only.
- `uploadScopePhotos($scopeId, $stage)` / `removeScopePhoto($photoId)` — multiple images per
  stage, `capture="environment"` so a phone opens the camera. Thumbnails render under each task.

**Derived status** (`app/Modules/VehicleInspectionOrder/Support/InspectionOrderStatus.php`)
Same doctrine as `AppointmentStatus` / `PickupDropStatus` — nobody types it. Ladder:

| Rung | Read off |
| --- | --- |
| Cancelled | deliberate act, sticks |
| Completed | every scope line completed |
| Work In Progress | any line running, or any line started/finished with none running |
| Work On Hold | a line paused and nothing else running |
| Assigned | a technician on the order |
| Assignment Pending | nothing yet |

`refresh()` also stamps `assigned_at` / `started_at` / `ended_at`, and clears `ended_at` when
work reopens. Called from the bench on start / pause / complete, and after an advisor save.

**Advisor page** (`.../VehicleInspectionOrder/Livewire/views/edit.blade.php`)
- Status is a read-only badge with one line of plain English for why it sits there.
- Completion type shows as a per-line summary; the order-level select is gone.
- Pause / Resume log is read-only. `syncPauses()` was **removed** — a stale form array would have
  deleted pauses the technician wrote after the page loaded, and rewritten rows without their
  scope/technician columns.
- Checklist keeps result + note; its per-item before/after uploads are gone.
- Technician Findings stays visible but read-only, with Approve / Revoke — the advisor confirms
  chargeable major work, they do not raise it.

## How to check on the UI

1. **Inspection → Technician Bench** — pick yourself, **Start** a task, then **Pause**: the reason
   modal must appear and refuse an empty reason. **Start** again and the pause closes.
2. Same row → **Complete** → pick a completion type.
3. Under each task, add several **Before** images, Save, then several **After** images; delete one.
4. **Inspection → Vehicle Inspection Orders** → open that order → **Status & Time Tracking**:
   status reflects the bench with no dropdown, the pause shows its reason, the line shows its
   completion type.

---

# VIO Listing page

| # | Requirement | Status |
| --- | --- | --- |
| 1 | Easy search (space or %) by vehicle + reg. no | ✅ Done |
| 2 | New search by From Date, To Date, Department, Service Type, Advisor | ✅ Done |
| 3 | New result columns — Inspection order Date & Time, Work Start, Work Complete, Vehicle name, Department, Service Type, Advisor, Bay No. | ✅ Done |
| 4 | Detail history / report comparing technician-wise TAT for particular jobs | ✅ Done |
| 5 | Sort on click for every column heading | ✅ Done |

## What changed

**Search was broken, not just narrow.** `$searchableFields` listed a bare `registration_no`,
which is not a column on `vehicle_inspection_orders` — every search threw. It now reaches the plate
through the job card, alongside VIN, model, brand and customer name. `%` and space both split
tokens (shared `Searchable` trait), so `GJ%16%FA%4311` and `HARRIER 4311` both land on the order.

**Filters** follow the pickup/drop pattern: search takes the width, status stays inline, everything
else lives behind **Filters** with active choices shown as removable chips. Added Department,
Service Type (narrowed to the chosen department), Advisor, and a From/To range whose target column
is chosen — ordered / work start / work complete / created — and whitelisted in `dateColumn()`.

**Columns** — VIO no, Ordered (date + time), Job Card, Vehicle (reg no + model), Department,
Service Type, Advisor, Technician, Bay, Work Start, Work Complete, Items, Priority, Status.
The table scrolls horizontally inside its own container.

**Sorting** — all fourteen headings. Related names use a correlated subquery via `sortExpression()`;
ordering by `technician_id` would sort by row id, which reads as random rather than alphabetical.

**Technician TAT** — `app/Modules/InspectionOrderHistory/Livewire/TechnicianTat.php`,
at `/inspection-order-tat`, menu **Inspection → Technician TAT**.
- The unit is the **work scope line**, not the order: one order can hold a clutch job and an oil
  change done by different people, and averaging those together says nothing.
- Time comes from `duration_seconds` — the technician's own clock, which excludes pauses they gave
  a reason for, so a job stalled waiting for parts is not held against them.
- A "job" is whatever names the line: job description, labour, package, requested repair, else the
  typed description.
- Columns: Job, Technician, Done, Average, Fastest, Slowest, Total, and **vs. shop average** —
  each technician measured against everyone else's average on that same job, which is what makes it
  a comparison rather than a list. All sortable. CSV export.
- Filters: job search, department, service type, technician, date range, completed-lines-only.

Verified with seeded data: two technicians on one job, 1h + 3h against 30m + 30m, gives a 1h 15m
shop average with the rows reading "45m slower" and "45m faster".

## How to check on the UI

1. **Inspection → Vehicle Inspection Orders** — search a plate with spaces, then the same with `%`.
2. **Filters** → pick a Department; the Service Type list narrows to it. Set a From/To range and
   switch "Date range applies to" between ordered / work start / work complete.
3. Click each column heading twice — it sorts, then reverses.
4. **Technician TAT** (button on the listing, or Inspection → Technician TAT) — compare two
   technicians on the same job; export the CSV.
