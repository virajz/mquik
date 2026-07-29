# Gate Pass Approval Request / Response — module 66

When a vehicle must be delivered against outstanding (partial / full credit, post-dated cheque, or
without an insurance DO), the advisor raises a gate-pass approval. The system captures invoice /
receipt / **outstanding / credit-exposure** amounts, routes to the approval authority by the amount
matrix (≤ ₹25k advisor/cashier, > ₹25k admin), and records the request → approved / rejected /
cancelled lifecycle. Header + document attachments (no line items).

## What's done

`app/Modules/GatePassApproval/` (`GPA-#####`, group **Sales**, route `gate-pass-approval.index`)
- Parent `gate_pass_approvals` + `gate_pass_approval_attachments`.
- Enums: `priorities` (normal / high / emergency), `creditTypes` (partial / full pending / post-dated
  cheque / without-DO), `creditReasons` (the 11-value list), `customerCommitments`, `securityDeposits`
  (cheque / cash), `riskTypes`, `riskCategories`, `approvalAuthorities` (advisor-cashier / admin-hr-owner),
  `statuses` (requested / under_review / on_hold / partial_pending_approved / full_pending_approved /
  rejected / cancelled), `cancellationReasons`. Attachment types: request letter / form / approval note /
  payment commitment / post-dated cheque.
- **Auto-calculation:** on invoice / receipt amount change (and on save) the module computes
  `outstanding_amount = invoice − receipt`, sets `credit_exposure = outstanding`, and **auto-selects the
  approval authority** from the matrix (`MATRIX_THRESHOLD = 25000`: ≤ 25k → advisor/cashier, > 25k →
  admin). The three derived figures are shown read-only on the form.
- **Reveal / conditional:** cancellation reason required on *Cancelled*.
- **Timestamps** stamped automatically: `requested_at` on create; `approved_at` on either approved
  status; `rejected_at` / `cancelled_at` on the matching status (first transition only).
- **Index**: KPIs (Pending Gate Pass Approvals / **Vehicles Delivered with Outstanding** / **Outstanding
  Amount Pending** — red), CSV **Gate Pass Report**.
- Tests: **11 green** (covers the matrix routing both sides of the threshold + live recompute).

## How to visually test on the UI

1. **Sales → Gate Pass Approval → New Approval.** Pick the **Customer**, **Vehicle** and **Job Card**;
   set the **Credit Type / Reason**, commitment, security deposit and risk.
2. Enter the **Invoice Amount** and **Receipt Amount** — the **Outstanding**, **Credit Exposure** and
   **Approval Authority** update live (enter an outstanding over ₹25k to see it flip to *Admin / HR /
   Owner*).
3. Move **Status** to *Full / Partial Pending Approved* (stamps approved-at) or *Cancelled* (reveals the
   required reason). Attach the customer request letter / payment commitment / PDC; save.
4. Index shows pending / delivered-with-outstanding counts and the total outstanding (red); **Gate Pass
   Report** exports the CSV.

## Related modules impacted

- **Reused (no changes):** Customer + Vehicle, JobCard, WorkshopDepartment, EmployeeMaster,
  FollowUpModeMaster.
- New permissions synced; menu under **Sales**.

## Effect on the system

Adds the credit-delivery gate: no vehicle leaves against outstanding without an amount-appropriate
approval, with outstanding / credit-exposure visibility. Additive — no existing module changed.

## Design note (interpretation — flag)

Of the spec's 11 **system-automation rules**, this module implements the *data + calculation* ones —
outstanding / receipt / credit-exposure auto-computation (rule 3) and matrix-based authority selection
(rule 4). The **enforcement / hand-off** rules are **not wired**: blocking normal gate-pass generation
for unauthorised-credit customers (1), the mandatory-before-delivery gate (2, 5), generating the gate
pass after approval (6), auto-assigning recovery + reminders (7, 8), and DO / PO status verification
before delivery (9, 10) all depend on a gate-pass-generation / delivery module and the recovery engine,
so they're follow-ups. The **₹25,000 matrix threshold** and the credit-authorised-customer list are
hard-coded here (`MATRIX_THRESHOLD`); the spec notes both are "customizable as per time" (via Account
Master) — moving them to configurable masters is a follow-up. The `po_reference` / `invoice_reference`
/ `receipt_reference` / `outstanding_reference` are free-text rather than typed FKs.
