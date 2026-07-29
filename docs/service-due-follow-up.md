# Service Due Follow-Ups — module 74

Track upcoming scheduled-service due follow-ups per vehicle: the due date / interval, the follow-up
attempts and customer response, escalation, retention and the conversion → job-card lifecycle, plus
recommended-service references. Header + note attachments.

## What's done

`app/Modules/ServiceDueFollowUp/` (`SDF-#####`, group **CRM**, route `service-due-follow-up.index`)
- Parent `service_due_follow_ups` + `service_due_follow_up_attachments`.
- Enums: `statuses` (pending / assigned / informed / accepted / quotation_sent / appointment_booked /
  converted / lost_opportunity / cancelled), `followUpAttempts`, `followUpModes`, `customerResponses`
  (the 11-value list), `satisfactions`, `lostReasons`, `escalations`, `escalationReasons`, `retentions`
  (active / lost / recovered), `intervalMethods` (kilometer / date), `reminderFrequencies`
  (before 15 / 7 days / today / after 1 week). Attachment types: customer note / follow-up notes.
- **References:** service history, estimate template, price list and recommended-service (AMC / combo /
  package) — free-text; the follow-up-by employee, customer + vehicle; due date, interval and odometer.
- **Reveal / conditional:** lost reason required when the status is *Lost Opportunity*; escalation
  reason required once an escalation is set; the appointment time shows for *Appointment Booked*.
- **Timestamps** stamped automatically: `due_generated_at` on create; `response_at` on first customer
  response; `appointment_at` on booking; `job_card_open_at` on *Converted*.
- **Index**: KPIs (Vehicles Due Today / Upcoming Due / **Overdue** (red) / Appointments Booked / Lost /
  Recovered), overdue rows highlighted red, CSV **Service Due Follow-Up Report**.
- Tests: **10 green**.

## How to visually test on the UI

1. **CRM → Service Due Follow-Ups → New Follow-Up.** Pick the **Customer** / **Vehicle** and follow-up
   owner; set the **Interval Method**, interval, **Due Date** and odometer.
2. In **Follow-up** set attempt / mode / **Customer Response** (stamps response-at); move **Status** to
   *Appointment Booked* (appointment time appears) or *Converted* (stamps job-card-open) or *Lost
   Opportunity* (lost reason required).
3. Set **Escalation** → reason required; set **Retention** (active / lost / recovered). Attach a
   customer / follow-up note; save.
4. Index shows the six KPIs (overdue in red), highlights overdue due-dates, and exports the **Service Due
   Follow-Up Report**.

## Related modules impacted

- **Reused (no changes):** EmployeeMaster (follow-up owner), Customer + Vehicle.
- New permissions synced; menu under **CRM**.

## Effect on the system

Adds the service-due CRM pipeline — from due-list follow-up through conversion or loss — with retention
tracking. Additive — no existing module changed.

## Design note (interpretation — flag)

This is the **most automation-heavy spec** in the batch; the module implements the **data + status +
lifecycle** and the **derived KPIs** (due today / upcoming / overdue via due-date vs today, appointments,
lost, recovered). The engine-side automation is **not wired**: auto-calculating due dates from last-2
services / odometer / service schedule (rule 1), generating the due list (2), auto-WhatsApp reminders
(3), auto-marking Overdue / Lost after grace/inactivity periods (5, 6), auto-closing on job-card
creation (7), management alerts for high-value overdue (8) and duplicate-prevention per vehicle/cycle
(9). Those need a scheduled job + the service-schedule / job-card integration and are follow-ups. The
service-history / estimate-template / price-list / package references are free-text rather than typed
FKs. The revenue-oriented KPIs (Follow-Up→Inward %, Inward→Revenue, advisor conversion %) are omitted —
they need cross-module job-card / invoice joins and belong in an analytics report.
