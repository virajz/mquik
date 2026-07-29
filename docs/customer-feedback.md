# Customer Feedback / Service Rating / Post-Service Follow-up — module 72

Capture post-service ratings (1–5) and feedback, and run the scheduled post-service follow-up (4 / 7 /
15 / 30 days after billing). Header carries the follow-up schedule / attempt, the rating answers, and
the sent → satisfied / dissatisfied / resolved lifecycle. Header + a feedback screenshot.

## What's done

`app/Modules/CustomerFeedback/` (`CF-#####`, group **CRM**, route `customer-feedback.index`)
- Parent `customer_feedbacks` + `customer_feedback_attachments`.
- Enums: `followUpSchedules` (4 / 7 / 15 / 30 days / custom), `followUpCategories`, `followUpModes`
  (WhatsApp / SMS / Email / Call), `followUpAttempts` (1st–final), `vehicleObservations`,
  `feedbackSources` (WhatsApp / Email / Website / Google / Mobile app / Reception), `feedbackCategories`
  (complaint / suggestion / praise), `statuses` (sent / satisfied / dissatisfied / under_investigation /
  resolved / cancelled). Attachment type: feedback screenshot.
- **Rating answers** (each 1–5): staff experience, service experience, service rating, price, on-time
  delivery, plus a **would-recommend** boolean. `averageRating()` averages whatever answers were given.
- **Timestamps** stamped automatically: `requested_at` on create; `submitted_at` when the status becomes
  satisfied / dissatisfied / resolved; `closed_at` on resolve. Custom days required when the schedule is
  *Custom*.
- **Index**: KPIs (**Average Customer Rating**, **Feedback Response Rate** = submitted ÷ total,
  **Negative Feedback** = dissatisfied or service-rating ≤ 2 — red) plus an **advisor-wise rating**
  panel; CSV **Customer Feedback** export.
- Tests: **10 green**.

## How to visually test on the UI

1. **CRM → Customer Feedback → New Feedback.** Set the **Follow-up Schedule** (choose *Custom* to reveal
   the required days), category, mode and attempt; pick the customer / vehicle / advisor and the
   feedback source.
2. Fill the **Ratings** (1–5 each) and tick **Would recommend**; set **Feedback Category** and move the
   status to *Satisfied* / *Dissatisfied* (stamps submitted-at). Attach the feedback screenshot; save.
3. Index shows the average rating, response rate, negative-feedback count (red) and an **advisor-wise
   rating** breakdown; **Customer Feedback** exports the CSV.

## Related modules impacted

- **Reused (no changes):** WorkshopDepartment, ServiceType, EmployeeMaster (advisor / technician),
  Customer + Vehicle, GatePassApproval (66, the feedback trigger point).
- New permissions synced; menu under **CRM**.

## Effect on the system

Adds post-service CSI capture and follow-up tracking, with advisor-wise rating rollups. Additive — no
existing module changed.

## Design note (interpretation — flag)

The rating **questions** are modelled as the five fixed 1–5 columns from the spec plus the recommend
boolean (rather than a configurable question bank). The automation rules — auto-send after gate pass,
link expiry, reminder-after-3-days, monthly advisor-rating rollups, duplicate-prevention and
management alerts — are **not wired** (send/schedule concerns, per the no-send policy); the advisor-wise
rating is computed live on the index instead of a stored monthly rollup. CSI = the average of the given
ratings via `averageRating()`.
