# Claim Intimation — module 20

Register an accidental insurance claim against a job card, capture policy + claim details, record
how/when the insurer was intimated, and set the survey turnaround so a surveyor can be notified.

## What's done

**New module** `app/Modules/ClaimIntimation/` (single table `claim_intimations`, `CI-#####`).
- Fields: job card, customer, vehicle, department, advisor employee; insurance company + policy type
  + **policy no**; **claim type** (reused ClaimTypeMaster — Cashless/Reimbursement), **claim no**
  (insurer's ref once intimated); **damage type** (Accident/Fire/Theft/Natural Calamity/Other);
  **intimation mode** (Email/Insurance Portal/API/Phone/Mobile App); **status**
  (Pending/Intimated/Cancelled); **pending reason** (Policy Expired/Delay in Intimation — reveals
  when Pending); **survey TAT** (within 24/48/72 hrs); `intimated_at` (required + revealed when
  status = Intimated); notes.
- Index with 4-tile dashboard (Pending / Intimated / Awaiting Survey / Cancelled), search
  (intimation/policy/claim/JC no), status + survey-TAT + insurer filters.
- **Survey Report** = the Index's **CSV export** (filtered set) — no separate module.

Tests: 12 green.

## How to visually test on the UI

1. **Insurance → Claim Intimation → New Claim.**
2. Link a **Job Card** + **Vehicle**, pick **Customer**, Department, Advisor.
3. **Policy & Claim**: pick **Insurance Company** + **Policy Type**, enter **Policy No**, pick
   **Claim Type** = *Cashless*, **Damage Type** = *Fire Damage*.
4. **Intimation**: pick **Intimation Mode** + **Survey TAG** (24/48/72 hrs). Set **Status** =
   *Pending* → confirm **Pending Reason** reveals; switch to *Intimated* → confirm **Intimated At**
   reveals and is required.
5. Save → back on index with a `CI-#####` row; dashboard tiles filter by status.
6. Click **Survey Report** (top right) → downloads the filtered CSV.

## Related modules impacted

- **Reused (no changes):** JobCard, CustomerMaster, CustomerVehicleMaster, WorkshopDepartmentMaster,
  EmployeeMaster, InsuranceCompanyMaster, InsurancePolicyTypeMaster, ClaimTypeMaster.
- New permissions synced; menu under **Insurance**.

## Effect on the system

Opens the insurance workflow: a claim record now exists to hang the Surveyor Inspection (21) and
Estimate Approval (23) off. Policies stay captured as number + type (consistent with how
`sales_estimates` already does it — there's no first-class policy record in the system). Additive.

## Design notes

- **Damage type** (Accident/Fire/…) is a model enum — the existing DamageCauseMaster has
  "ACCIDENT DAMAGE" but no Fire, and DamageTypeMaster is physical-damage (dent/scratch); a small
  self-contained enum is cleaner than overloading either.
- **Pending reason** and **intimation mode / survey TAT** are enums — the existing PendingReasonMaster
  (Customer Request/Traffic Issue) doesn't fit claim-intimation reasons.
- The "Survey Report" deliverable is the Index's filtered CSV export rather than a separate report
  module, to keep the surface tight.
