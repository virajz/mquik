# Service Recommended Follow-Ups — module 79

Follow-ups based on technician-recommended future services (raised at invoice / inspection time): the
recommendation type / reason / category, the follow-up attempts and customer response, escalation,
retention and the → converted / lost lifecycle. Header + note attachments.

## What's done

`app/Modules/ServiceRecommendationFollowUp/` (`SRF-#####`, group **CRM**, route
`service-recommendation-follow-up.index`)
- Parent `service_recommendation_follow_ups` + `service_recommendation_follow_up_attachments`.
- Enums: `recommendationTypes` (engine / gearbox / … / PMS), `recommendationReasons`,
  `recommendationCategories` (safety / preventive / performance / comfort / regulatory), `priorities`,
  `statuses` (pending → assigned → informed → accepted → quotation_sent → appointment_booked →
  converted / lost_opportunity / cancelled), `reminderFrequencies`, `followUpAttempts`, `followUpModes`,
  `customerResponses` (14-value list incl. serviced-elsewhere), `escalations`, `escalationReasons`,
  `satisfactions`, `retentions`, `lostReasons`. Attachment types: customer note / follow-up notes.
- **Recommended service** is free-text (e.g. "Tyre Replace"); links invoice / inspection / estimate /
  price-list references and an `estimated_value` (potential revenue).
- **Reveal / conditional:** lost reason required on *Lost Opportunity*; escalation reason required once
  an escalation is set.
- **Timestamps** stamped automatically: `recommended_at` on create; `informed_at` on *Informed*;
  `estimate_at` on *Quotation Sent*; `appointment_at` on *Appointment Booked*.
- **Index**: KPIs (Open / Today's / Upcoming / **Safety-Critical** (red) / **Conversion Rate** / Lost),
  safety recommendations flagged with a red badge; CSV **Service Recommended Follow-Up Report**.
- Tests: **9 green**.

## How to visually test on the UI

1. **CRM → Service Recommendation Follow-Ups → New Recommendation.** Pick the **Customer** / **Vehicle**,
   describe the **Recommended Service**, set type / reason / **Category** (choose *Safety* to see the red
   highlight), priority and an estimated value.
2. In **Follow-up** set owner, attempt / mode / **Customer Response**; move **Status** to *Appointment
   Booked* (stamps appointment-at) or *Converted* or *Lost Opportunity* (lost reason required). Set an
   escalation → reason required.
3. Attach a customer / follow-up note; save. Index shows the KPIs incl. safety-critical count and
   conversion rate; the report exports the CSV.

## Related modules impacted

- **Reused (no changes):** WorkshopDepartment, EmployeeMaster, Customer + Vehicle.
- New permissions synced; menu under **CRM**.

## Effect on the system

Adds the recommended-service upsell pipeline, with safety-critical flagging and conversion tracking.
Additive — no existing module changed.

## Design note (interpretation — flag)

The spec's automation rules — auto-create a recommendation from an invoice's future-repair items,
auto-schedule follow-ups, auto WhatsApp/Email, auto-generate an appointment/estimate on acceptance,
auto-Convert on job-card creation, safety-critical alerts, duplicate-prevention per vehicle, and live
KPI/revenue updates — are **not wired** (invoice ingestion + notifications + scheduled jobs +
cross-module hooks, per the no-send policy). Safety-critical is *surfaced* (red badge + KPI). The
advisor-wise conversion analytics are omitted — they belong in an analytics report.
