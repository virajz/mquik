# Sales Inquiry (Parts / Service) Entry — module 75

Capture a customer inquiry for service / parts from any channel (walk-in, phone, WhatsApp, web, app),
assign it, follow it up and track it through to conversion or loss. **Also covers module 76 (Sales
Inquiry Follow-Ups)** — the follow-up attempt / escalation / status live on this record. Header +
inquiry-evidence attachments.

## What's done

`app/Modules/SalesInquiry/` (`INQ-#####`, group **CRM**, route `sales-inquiry.index`)
- Parent `sales_inquiries` + `sales_inquiry_attachments`.
- Enums: `inquiryTypes` (the 13-value list), `inquirySources` (9), `priorities`, `statuses` (pending /
  assigned / quotation_sent / appointment_booked / converted / lost_opportunity / cancelled),
  `followUpAttempts`, `escalations`, `escalationReasons`, `lostReasons`. Attachment types: VIN photo /
  vehicle photos / voice recording (accepts image / pdf / audio).
- Links customer + vehicle, department, assigned-by / assigned-to employees; carries an `inquiry_details`
  text and an `estimated_value` (potential revenue).
- **Reveal / conditional:** lost reason required on *Lost Opportunity*; escalation reason required once
  an escalation is set.
- **Timestamps** stamped automatically: `inquiry_at` on create; `assigned_at` when an assignee is set;
  `quotation_at` on *Quotation Sent*; `converted_at` on *Converted*; `closed_at` on *Lost / Cancelled*.
- **Index**: KPIs (New Today / Open / Quotations Sent / Converted / Lost / **Conversion Rate**), a
  **Revenue Generated** figure and a **source-wise** inquiry breakdown; CSV **Sales Inquiry Report**.
- Tests: **10 green**.

## How to visually test on the UI

1. **CRM → Sales Inquiries → New Inquiry.** Pick the **Customer** / **Vehicle**, the **Inquiry Type** and
   **Source**, priority, an estimated value and details.
2. In **Assignment & Follow-up** set assigned-by / assigned-to (stamps assigned-at) and move **Status**
   to *Quotation Sent* (stamps quotation-at) → *Converted* (stamps converted-at) or *Lost Opportunity*
   (lost reason required). Set an escalation → reason required.
3. Attach a VIN / vehicle photo or voice note; save. The index shows the six KPIs, revenue generated and
   a source-wise breakdown; **Sales Inquiry Report** exports the CSV.

## Related modules impacted

- **Reused (no changes):** Customer + Vehicle, WorkshopDepartment, EmployeeMaster (assigned by / to).
- New permissions synced; menu under **CRM**.

## Effect on the system

Adds the top-of-funnel inquiry capture + follow-up pipeline for service / parts, with conversion and
source analytics. Additive — no existing module changed.

## Design note (interpretation — flag)

Module **76 (Sales Inquiry Follow-Ups)** was **not built** — the spec marks it "covered in above"; the
follow-up attempt / escalation / status all live on the inquiry record here. The spec's automation rules
(auto-capture of web / app / WhatsApp / API inquiries, auto-assignment by category, WhatsApp
acknowledgement, follow-up reminders, auto-Convert on job-card / counter-sale creation, duplicate
detection, auto-updating conversion stats) are **not wired** — channel ingestion + notification +
cross-module hooks, per the no-send policy. "Inventory – Spare Parts / Labour" is captured as free-text
`inquiry_details` rather than a line-item child (an inquiry is lightweight; add a child table later if
you want itemised inquiries). The advisor-wise response/conversion analytics are omitted — they belong
in an analytics report.
