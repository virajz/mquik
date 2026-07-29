# Queue Management System — module (row 28, queue variant)

A live service-queue / display board for quick services (AC service, alignment/balancing, PDI, car
wash, detailing) with FIFO/priority ordering, high-priority approval, per-vehicle timing and a
6-KPI dashboard.

## What's done

**New module** `app/Modules/QueueManagement/` (single table `service_queues`, `Q-#####`).
- Fields: **queue type** (AC / Alignment-Balancing / PDI / Car Wash / Detailing), job card, vehicle,
  **service labour** (reused LabourMaster), technician, job description; **ordering** (FIFO /
  Priority Based); **screen view** (Upcoming / Arrived / Ready); **status** (Waiting / In Progress /
  On Hold / Ready / Completed / Cancelled).
- **High priority**: toggle + reason (Customer Waiting / VIP / Delivery Commitment / Management /
  Emergency, required when on) + **Approval Requested By / To** employees; high-priority rows sort to
  the top of the board.
- **Reason enums**: rework (Customer Complaint / Advisor Rejected / Dirty / Missed Area / Other),
  delay (Water Supply / Power / Technician Unavailable / High Workload / Other), pause (Equipment
  Failure / Water Shortage / Power Failure — required when On Hold).
- **Timestamps**: kept-for-service, work start, work end, promised delivery, expected completion —
  driving `waitingMinutes()` (kept→start), `serviceMinutes()` (start→end), `tatMinutes()` (kept→end),
  and `isOnTime()` (ended ≤ expected/promised).
- **Dashboard KPIs**: Pending, Completed, Total, **Avg Waiting**, **Avg Washing**, **On-Time %**.
- Board Index filtered by screen view / type / status, with search and CSV export.

Tests: 14 green (incl. waiting/service/TAT math, on-time flag, and the KPI on-time percent).

## How to visually test on the UI

1. **Workshop → Queue Management → Add to Queue.**
2. Pick **Queue Type** = *Car Wash*, a **Service (Labour)**, **Vehicle**, enter **Job Description**,
   assign a **Technician**.
3. **Ordering & Priority**: toggle **High Priority** → confirm **Reason** reveals (required) and
   Approval By/To fields; set **Screen View**.
4. **Timing & Status**: set kept / work-start / work-end times; set **Status** = *On Hold* → confirm
   **Pause Reason** reveals (required). Optionally set delay / rework reasons.
5. Save → the board shows the row (with an **HP** badge if high-priority, sorted to top); the 6 KPI
   tiles update; **Export** downloads the queue CSV.

## Related modules impacted

- **Reused (no changes):** LabourMaster (service), JobCard, CustomerVehicleMaster (+ its
  VehicleModel for the model display), EmployeeMaster (technician + approvers).
- New permissions synced; menu under **Workshop**.

## Effect on the system

Gives the wash/quick-service bays a real queue with priority handling and time analytics
(waiting/washing/TAT + on-time %), feeding productivity visibility. Additive.

## Design notes

- Queue type / screen view / ordering / high-priority / rework / delay / pause reasons and status are
  model enums (self-contained). The spec's note that pause reasons are "enable/disable configurable in
  a master" is not built as a separate master — the pause-reason set lives on the model; can be
  promoted to a master later if the shop needs per-branch toggles.
- "Next service alert / Vehicle completion message" are notifications — **not sent** (consistent with
  the project's config-only messaging policy).
- The "Display Configuration" (which columns to show) is delivered as a fixed, sensible column set on
  the board rather than a per-user column-picker.
