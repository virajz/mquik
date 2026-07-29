# Delivery Order (DO) Request / Response — module 56

After a proforma is approved, the workshop sends it to the insurance company, which verifies items /
rates / total and issues a **Delivery Order (DO)** — the confirmation to deliver the vehicle. This
records the DO against the claim, captures the request / received / entry / approved timestamps and the
DO amount, and **flags any mismatch vs the proforma amount**. Header + document attachments.

## What's done

`app/Modules/DeliveryOrder/` (`DO-#####`, group **Insurance**, route `delivery-order.index`)
- Parent `delivery_orders` + `delivery_order_attachments`.
- Links the module-55 **Proforma Approval**, job card, department, employee, insurance company,
  customer + vehicle; captures **surveyor** (free-text, per the existing surveyor convention) and the
  **insurance claim reference** (claim number / date, policy number).
- Enums: `statuses` (requested / under_verification / on_hold / do_received / requested_to_settle /
  mismatch_approved / cancelled), `mismatchReasons` (labour / parts / paint reduction, depreciation,
  non-approved item, policy limitation), `reminderFrequencies` (config-only). Attachment types:
  proforma copy / DO copy / surveyor consent / customer consent.
- **Amount comparison:** `proforma_amount` auto-fills from the linked proforma; `hasMismatch()` /
  `mismatchDelta()` compare it against the entered `do_amount`. A mismatch reveals (and requires) a
  **mismatch reason**, and is shown in **red** on the form and the index.
- **Timestamps** stamped automatically: `requested_at` on create; `do_received_at` when status reaches
  DO Received / Mismatch Approved / Requested-to-Settle; `approved_at` on Mismatch Approved. `do_entry_at`
  is an editable field.
- **Index**: KPIs (DO Pending / DO Received / **Amount Mismatch Cases** — red when > 0), CSV **Delivery
  Order Report**.
- Tests: **10 green**.

## How to visually test on the UI

1. **Insurance → Delivery Order → New DO.** Link a **Job Card** and the **Proforma Ref** (the proforma
   amount auto-fills), pick the insurance company, customer + vehicle, surveyor and claim details.
2. Enter the **DO Amount** — if it differs from the proforma, a **red mismatch warning** appears and a
   **Mismatch Reason** becomes required.
3. Set **DO Status = DO Received** (stamps received-at); attach the DO copy / proforma copy / consents;
   save. The index highlights mismatched DO amounts in red and counts **Amount Mismatch Cases**.

## Related modules impacted

- **Reused (no changes):** ProformaApproval (55), JobCard, WorkshopDepartment, EmployeeMaster,
  InsuranceCompanyMaster, Customer + Vehicle, FollowUpModeMaster.
- New permissions synced; menu under **Insurance**.

## Effect on the system

Records the insurance DO gate before vehicle delivery and surfaces proforma-vs-DO amount mismatches for
settlement. Additive — no existing module changed.

## Design note (interpretation — flag)

The spec's **system-automation rules** (enable DO request when proforma approved; auto-compare on DO
received; fire a red mismatch alert; enable vehicle delivery on DO approved) are modelled as **data +
UI state**, not background automation: the mismatch is computed and shown in red, statuses gate the
flow, and reminders are config-only. Enabling the actual "enable delivery process" hand-off to the
delivery module is a follow-up if you want it wired.
