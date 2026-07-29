# Job Card Cancel Approval — module 24

Admin approval workflow for cancelling a job card: cancellation type/reason, a 4-level approval
hierarchy, the downstream impacts, and refund status.

## What's done

**New module** `app/Modules/JobCardCancelApproval/` (single table `job_card_cancel_approvals`,
`JCA-#####`).
- Fields: job card, department, **job type** (= ServiceTypeMaster — there is no JobType master),
  requested-by employee, **cancellation type** (Wrong Entry / Customer Rejection / Insurance
  Rejection / Duplicate / Internal Error / Operational Issue), **cancellation reason** (reused
  JobCardCancelReasonMaster, seeded "DELAY IN SERVICE"), **approval level** (L1 Advisor → L2 Store →
  L3 Accounts → L4 Workshop/Owner), **status** (Pending/Requested/Under Review/Approved/Rejected/
  Cancelled/Reversed), **cancellation impact** (multi-select JSON: return to vendor / reverse stock /
  issue in consumption / return labour & warranty / refund / adjust next job), **approval rejection
  reason**, **refund status**, follow-up mode; `decided_at` (required + revealed when Approved/
  Rejected).
- Index with 4-tile dashboard (Pending / Under Review / Approved / Rejected-Cancelled), search,
  status + type filters, and the **Job Card Cancel Report** (CSV export).

Tests: 12 green (incl. multi-select impact persistence and approve/reject gating).

## How to visually test on the UI

1. **Workshop → Job Card Cancel Approval → New Request.**
2. Link a **Job Card**, pick **Requested By**, Department, **Job Type**.
3. Pick **Cancellation Type** = *Duplicate Job Card* and a **Cancellation Reason**.
4. Set **Approval Level** and **Status** = *Approved* → confirm **Decided At** reveals and is
   required; set *Rejected* → **Approval Rejection Reason** reveals and is required.
5. **Impact & Refund**: tick multiple **Cancellation Impact** boxes; set **Refund Status**.
6. Save → back on index with a `JCA-#####` row; tiles filter by status; **Report** exports CSV.

## Related modules impacted

- **Reused (no changes):** JobCard, WorkshopDepartmentMaster, ServiceTypeMaster, EmployeeMaster,
  FollowUpModeMaster.
- **Seeder extended:** JobCardCancelReasonMaster (+ DELAY IN SERVICE).
- New permissions synced; menu under **Workshop**.

## Effect on the system

Adds a governed cancellation path for job cards with a level-based hierarchy and an explicit record
of downstream impacts (stock reversal, refund, warranty return) so cancellations are auditable rather
than silent deletes. Additive.

## Design notes

- **"Job type"** maps to ServiceTypeMaster (no JobTypeMaster exists). Cancellation type / approval
  level / status / impact / approval-rejection-reason / refund status are model enums; **impact** is a
  JSON multi-select. The "Job Card Cancel Report" is the Index's filtered CSV export.
