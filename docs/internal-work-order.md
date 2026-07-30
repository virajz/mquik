# IWO — Internal Work Order (Request & Response) — module 92

A centralized register for internal complaints / requests / work orders / suggestions raised across
departments (IT, HR, Stores, Security, Housekeeping, Maintenance, …), with the management response,
root-cause / corrective-action, escalation and per-stage timestamps for TAT tracking. Header + file
attachments. Doc series `IWO-#####`.

## What's done

`app/Modules/InternalWorkOrder/` (`IWO-#####`, group **HR**, route `internal-work-order.index`)
- Parent `internal_work_orders` + `internal_work_order_attachments`.
- Auto **iwo_no** = `IWO-#####` (5-digit id), stamped in booted() created hook.
- Enums (static methods, TitleCase): `types` (raise complaint / request / work order / suggestion),
  `categories` (19 — CCTV / Biometric / Software / … / Other), `priorities` (normal / medium / high),
  `departments` (HR / Account / IT / CRM / Stores / Security / Housekeeping / Management),
  `statuses` (requested → under_review → on_hold → in_progress → resolved / cancelled),
  `responses` (13 — accepted / more-info / awaited / … / temporary resolution),
  `followUpModes` (WhatsApp / Email / SMS / Mobile App / Call / Visit),
  `escalations` (to HR / to Owner), `rootCauses` (7), `correctiveActions` (8).
- Requester / approver / handler are all employees (`requested_by` / `requested_to` / `assigned_to`).
- **Timestamps** stamped automatically: `complaint_at` on create; `assigned_at` first time a handler is set;
  `work_started_at` when status → In Progress; `resolved_at` when → Resolved; `cancelled_at` when → Cancelled.
  A `due_at` drives the overdue KPI.
- **Conditional:** `corrective_action` required when status = *Resolved*.
- Attachments: image / video / PDF / screenshot (video uploads allowed — mp4/mov/webm, 20 MB).
- **Dashboard KPIs**: Total / Open / Assigned / **Overdue** (open & past due, red) / Resolved Today /
  **Avg TAT (hrs)** (mean resolved − complaint). CSV **IWO Register Report**.
- Tests: **9 green** (stable over 5 runs).

## How to visually test on the UI

1. **HR → Internal Work Order → New IWO.** Set **Type** / **Category** / **Priority**, a title + description,
   the **Department** and Requested-By / Requested-To; save → number `IWO-#####` (stamps complaint-at).
2. Reopen: set **Assigned To** (stamps assigned-at), move **Status** to *In Progress* (stamps work-started),
   set an **IWO Response** / follow-up mode; set a **Due By** to test the overdue KPI.
3. Move to *Resolved* → **Corrective Action** becomes required (stamps resolved-at, feeds Avg TAT). Attach an
   image/video/PDF. Index shows the six KPIs (overdue red) and the register report exports CSV.

## Related modules impacted

- **Reused (no changes):** EmployeeMaster (requester / approver / handler).
- New permissions synced; menu under **HR**.

## Effect on the system

Adds the internal-operations complaint / work-order register with TAT analytics — additive, no existing
module changed.

## Design note (interpretation — flag)

- **Group:** placed in **HR** (the populated internal-ops group; it has HR escalation + employee
  requester/handler semantics). Trivially moved to a dedicated Facilities/Admin group if preferred.
- **Department** is a fixed enum (the spec's 8 internal departments), not a FK to `DepartmentMaster`, to match
  the spec's list exactly — swap to the master if you want it data-driven.
- **`due_at`** is an added field (the spec had no explicit due date but a Dashboard "Overdue Tasks" KPI, which
  needs one). Overdue = open & past due.
- Automation is **not wired** (auto-assign / route by category, auto-notify via the follow-up mode, SLA
  auto-escalation, live dashboard) — per the no-send policy this is the data + status + stamped-TAT model with
  KPIs computed on load. Avg TAT is whole hours.
