# Customer Complaints — module 71

Register and track the resolution of a customer (or internal) complaint — type, source, assignment, root
cause, resolution, satisfaction scores and the investigation → resolved / rejected / reopened lifecycle
with per-stage timestamps. Header + document / media attachments.

## What's done

`app/Modules/CustomerComplaint/` (`CMP-#####`, group **CRM**, route `customer-complaint.index`)
- Parent `customer_complaints` + `customer_complaint_attachments`.
- Enums: `complaintTypes` (the 16-value list), `complaintSources` (9), `priorities` (normal / medium /
  high), `assignments` (CRM exec / advisor / store / billing / admin), `rootCauses` (9),
  `resolutionTypes` (rework / part replace / CN / refund / discount / apology / warranty settlement),
  `statuses` (under_investigation / pending_customer / pending_vendor / resolved / rejected / cancelled),
  `reopenReasons`. Attachment types: complaint copy / video / voice recording / investigation /
  resolution copy (accepts image / pdf / video / audio, `kind` detected per file).
- **Satisfaction:** `achieved_score` and `recommended_score` (1–5); **the achieved score is required to
  resolve** a complaint.
- **Timestamps** stamped automatically: `opened_at` on create; `assigned_at` the first time an
  assignment is set; `closed_at` on resolve; `reopened_at` the first time a reopen reason is given.
- **Repeat-Job complaints are highlighted** (a red "Repeat" badge) on the index.
- **Index**: KPIs (Complaints Received / Open / Resolved / **Avg Customer Satisfaction**), CSV **Customer
  Complaint Report**.
- Tests: **11 green**.

## How to visually test on the UI

1. **CRM → Customer Complaints → New Complaint.** Pick a **Complaint Type** (choose *Repeat Job* to see
   the red highlight), source, priority, the customer / vehicle / job card, and a description.
2. In **Handling** set the assignment (stamps assigned-at), root cause and resolution.
3. Set **Status = Resolved** — an **Achieved Score (1–5)** becomes required (stamps closed-at). Attach a
   complaint video / voice note / resolution copy; save.
4. Index KPIs show received / open / resolved and the average satisfaction; **Customer Complaint Report**
   exports the CSV.

## Related modules impacted

- **Reused (no changes):** WorkshopDepartment, ServiceType, EmployeeMaster (opened-by / advisor /
  technician), JobCard, Customer + Vehicle.
- New permissions synced; menu under **CRM**.

## Effect on the system

Adds the complaint-management workflow with root-cause / resolution capture and CSI scoring. This is the
customer-side complaint register (distinct from the vendor-facing warranty returns in modules 43 & 51).
Additive — no existing module changed.

## Design note (interpretation — flag)

The spec's automation rules are partially modelled: unique complaint number (yes, `CMP-`), repeat-job
highlighting (yes), and mandatory satisfaction score before closure (yes, enforced). The **WhatsApp /
email acknowledgement** on registration and **auto-deletion of media after a retention period** are
config/notification + scheduled-job concerns and are **not wired** (per the project's no-send policy) —
follow-ups if you want them. Modules **69 (Customer Warranty Claim)** and **70 (Vendor Warranty Claim)**
were **not built** — the client spec marks both as "already covered in sr. 43 & 51".
