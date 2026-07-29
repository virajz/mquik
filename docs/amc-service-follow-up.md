# AMC Service Due / Renewal Follow-Ups — module 81

Reminders and tracking for AMC due services and AMC renewals: the AMC reference, due date / interval,
follow-up attempts and customer response, escalation, retention and the → converted / overdue / lost
lifecycle. Header + note attachments.

## What's done

`app/Modules/AmcServiceFollowUp/` (`ASF-#####`, group **CRM**, route `amc-service-follow-up.index`)
- Parent `amc_service_follow_ups` + `amc_service_follow_up_attachments`.
- Enums: `followUpTypes` (**service_due / amc_renewal**), `statuses` (pending / informed /
  quotation_sent / appointment_booked / converted / overdue / lost_opportunity / cancelled),
  `followUpAttempts`, `followUpModes`, `customerResponses`, `satisfactions`, `lostReasons`,
  **`missedServiceReasons`** (customer busy / vehicle unavailable / out of station / no response /
  vehicle sold), `escalations`, `escalationReasons` (incl. high-value AMC / missed-multiple),
  `retentions`, `intervalMethods` (km / date), `reminderFrequencies` (before 7 / today / after 3 / after
  7 days). Attachment types: service schedule / customer note / follow-up notes.
- **Links the Vehicle AMC (80)**, follow-up owner, customer + vehicle; carries the service interval /
  due date / odometer and estimate-template / price-list / package references.
- **Reveal / conditional:** lost reason required on *Lost Opportunity*; escalation reason required once
  an escalation is set; appointment time shows for *Appointment Booked*.
- **Timestamps** stamped automatically: `due_generated_at` on create; `response_at` on first customer
  response; `appointment_at` on booking; `job_card_open_at` on *Converted*.
- **Index**: KPIs (Due Today / Upcoming / **Overdue** (red) / Appointments / Lost / Recovered),
  service-due vs AMC-renewal badge, overdue rows highlighted; CSV **AMC Service Due Follow-Up Report**.
- Tests: **9 green**.

## How to visually test on the UI

1. **CRM → AMC Service Due / Renewal Follow-Ups → New Follow-Up.** Set the **Follow-up Type** (Service
   Due / AMC Renewal), link the **Vehicle AMC**, customer + vehicle, interval method / interval / due
   date / odometer.
2. In **Follow-up** set attempt / mode / **Customer Response** (stamps response-at) or a **Missed Service
   Reason**; move **Status** to *Appointment Booked* / *Converted* (stamps job-card-open) or *Lost
   Opportunity* (lost reason required). Set escalation → reason required; set retention.
3. Attach a service schedule / note; save. Index shows the six KPIs (overdue red) and the report exports
   the CSV.

## Related modules impacted

- **Reused (no changes):** VehicleAmc (80), EmployeeMaster, Customer + Vehicle.
- New permissions synced; menu under **CRM**.

## Effect on the system

Completes the AMC lifecycle — **sell (80) → due-service / renewal follow-up (81)** — with conversion,
retention and overdue tracking. Additive — no existing module changed.

## Design note (interpretation — flag)

This is heavily automation-specced; the module implements the **data + status + lifecycle** and the
**derived KPIs**. The engine-side rules are **not wired**: auto-calc next AMC due date from package rules
(time / km / whichever first), auto-generate due records for active AMCs, auto WhatsApp/Email/App
reminders, auto-assign to CRM, auto-Appointment-Booked on booking, the post-job-card cascade (mark
complete → update the AMC utilisation ledger → calc next due → generate the next follow-up), auto-Overdue
on due-date pass, auto-flag-for-renewal on AMC expiry, duplicate-prevention per interval, and live KPI
updates. Those need a scheduled job + the AMC package-rules engine + cross-module job-card hooks. The
revenue-oriented KPIs (Follow-Up→Inward %, Inward→Revenue, advisor conversion %) are omitted — they
belong in an analytics report. References (vehicle history / estimate template / price list / package)
are free-text.
