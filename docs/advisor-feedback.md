# Advisor Feedback / Advisor Rating — module 73

The reverse of customer feedback (module 72): the **service advisor rates the customer** after a job —
cooperation, timely approvals, payment as committed, professionalism, and whether they'd handle them
again. Header only (no attachments) — five 1–5 rating answers and a pending → submitted lifecycle.

## What's done

`app/Modules/AdvisorFeedback/` (`AF-#####`, group **CRM**, route `advisor-feedback.index`)
- Parent `advisor_feedbacks` (no child tables).
- The **five questions** are exposed via `questions()` (keyed by their rating column) and rendered as a
  question → 1–5 star list; `averageRating()` averages whatever answers were given.
- Enums: `statuses` (pending / submitted / cancelled). Ratings validate 1–5.
- **Timestamp:** `submitted_at` stamped the first time the status becomes *Submitted*.
- Links invoice reference, **Gate Pass approval (66)**, department / service type, advisor / technician,
  customer + vehicle.
- **Index**: KPIs (Pending / Submitted / **Avg Customer Rating**), CSV **Advisor Feedback** export.
- Tests: **10 green**.

## How to visually test on the UI

1. **CRM → Advisor Feedback → New Feedback.** Pick the **Customer**, **Vehicle**, **Advisor** and job
   references.
2. Rate the customer **1–5** on each of the five questions; set **Status = Submitted** (stamps
   submitted-at); save.
3. Index shows pending / submitted counts and the average customer rating; **Advisor Feedback** exports
   the CSV.

## Related modules impacted

- **Reused (no changes):** WorkshopDepartment, ServiceType, EmployeeMaster (advisor / technician),
  Customer + Vehicle, GatePassApproval (66).
- New permissions synced; menu under **CRM**.

## Effect on the system

Adds the advisor-side counterpart to customer feedback (72) — a customer-behaviour rating that feeds
future credit / handling decisions. Additive — no existing module changed.
