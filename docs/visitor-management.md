# VMS — Visitor Management System — module 82

Reception front-desk flow: a visitor arrives, gets a token, is assigned a service advisor, has a
consultation, and either a job card is created or the visit is cancelled — with per-stage timestamps and
queue/waiting-time KPIs. Header + note attachments.

## What's done

`app/Modules/VisitorManagement/` (`VMS-#####`, group **Workshop**, route `visitor-management.index`)
- Parent `visitor_visits` + `visitor_visit_attachments`.
- Auto **token_no** = `VMS-#####` (5-digit id), stamped in booted() created hook.
- Links customer + vehicle, department, `assigned_by` (reception exec) and `assigned_to` (advisor / CRM).
- Enums (static methods, TitleCase): `customerTypes` (senior citizen / lady / gents), `visitPurposes`
  (periodic maintenance / general repair / accident / insurance claim / delivery / surveyor / consultation),
  `arrivalModes` (walk-in / appointment / phone / CRM follow-up / mobile app / website),
  `advisorAssignmentMethods` (auto / manual / least-busy / preferred), `advisorAvailabilities`
  (available / busy / on break / meeting / lunch / leave), `waitingTimeCategories` (<10 … >60 min),
  `waitingStatuses`/`statuses` (advisor_assigned → consultation_started → consultation_completed →
  job_card_created / cancelled), `delayReasons`, `noShowReasons`. Plus `announcement_message`.
- **Timestamps** stamped automatically: `arrival_at` on create; `advisor_assigned_at` first time an advisor
  is set; `consultation_started_at` / `consultation_ended_at` / `job_card_created_at` on the matching status;
  `exit_at` first time status is job-card-created or cancelled.
- **Conditional:** `no_show_reason` required when status = *Cancelled*.
- **Index KPIs**: Tokens Generated Today / Customers Served Today / No-Shows / **Avg Waiting Time (min)**
  (mean of consultation-start − arrival). CSV **Visitor Management Report**.
- Tests: **9 green** (stable over 4 runs).

## How to visually test on the UI

1. **Workshop → Visitor Management → New Visitor.** Pick customer / vehicle, **Customer Type**, **Visit
   Purpose**, **Arrival Mode**; assign an advisor (stamps advisor-assigned) and set the estimated waiting-time
   category.
2. Advance **Waiting Status** through consultation-started → consultation-completed → job-card-created
   (each stamps its timestamp; job-card stamps exit). Or set *Cancelled* → **No-Show Reason** required.
3. Attach a token slip / note; save (token is `VMS-#####`). Index shows the four KPIs; the report exports CSV.

## Related modules impacted

- **Reused (no changes):** CustomerMaster, CustomerVehicleMaster, WorkshopDepartmentMaster, EmployeeMaster.
- New permissions synced; menu under **Workshop**.
- **Overlaps `QueueManagement`** (the service-bay queue — car wash / alignment / PDI). VMS is the *reception
  desk* visitor/consultation flow; QueueManagement is the *workshop bay* queue. They're complementary, not
  duplicates — a visit here can lead to a job card, which then feeds the bay queue.

## Effect on the system

Adds the front-desk visitor pipeline (token → advisor → consultation → job card) with waiting-time analytics.
Additive — no existing module changed.

## Design note (interpretation — flag)

The spec's **System Automation Rules are not wired**: auto-token-generation from the Vehicle Inward /
ANPR-camera feed (customer + vehicle auto-fetch), automatic waiting-time recalculation on cancel/complete/
reassign, auto No-Show / Skipped after N recall attempts, and real-time dashboard/advisor-workload/queue
analytics. These need cross-module inward hooks + a scheduled/recall job + live broadcasting. Implemented
here: the **data model, the token, the stage timestamps, the derived KPIs** (computed on load, not live).
The queue display board (token / advisor / est. wait / announcement) is data-backed via the columns; a live
display screen is a separate view.
