# Sales Estimate Approval — module 23

Customer / advisor / insurer approval of a sales estimate, line by line, with depreciation and an
8-state lifecycle.

## What's done

**New module** `app/Modules/SalesEstimateApproval/` (`SEA-#####`).
- Parent `sales_estimate_approvals`: job card, **estimate** ref, **surveyor inspection** ref (module
  21), insurer, customer, vehicle, department, service type, advisor; **approval type**
  (Regular/Supplementary/Additional/Revised); **approval authorisation** (Insurance/Customer/Both/
  Internal/Management); **approval mode** (reused CustomerApprovalTypeMaster — WhatsApp/Email/Digital
  Signature/…); **parts brand preference** (Genuine/Aftermarket/Any); **rejection reason** (7 values,
  required when Rejected); reminder frequency (+ custom days) + **follow-up mode** (FollowUpModeMaster);
  8-state **approval status** (Sent / Partially / Fully Approved / Query Raised / Revised & Resent /
  No Response / Rejected / Cancelled); customer/insurance approved-at timestamps + a derived
  `approved_at` (stamped when Fully Approved — drives the KPI).
- Line items `sales_estimate_approval_items`: **spare / labour / package**, inventory group, HSN,
  tax, qty, rate; **per-line decision** (Repair/Replace/Remove&Refit/Approved/Rejected/Pending);
  **depreciation** by part category (Plastic/Metal/Rubber/Glass) + percent, with Net-after-dep shown
  live.
- Index **Dashboard KPIs**: Pending Customer Approvals, Pending Insurance Approvals, Approved Today,
  and **Approved on Date** (with an inline date picker) — matching the spec. Plus status/authorisation/
  insurer filters and the **Estimate Analysis Report** (CSV export).

Tests: 11 green (incl. depreciation math, approved-at stamping, and the pending-customer/insurance
KPI logic).

## How to visually test on the UI

1. **Insurance → Sales Estimate Approval → New Approval.**
2. **References**: link Job Card, **Estimate**, insurer, department, service type.
3. **Approval Setup**: pick **Type** = *Regular*, **Authorisation** = *Insurance & Customer*,
   **Mode** = *WhatsApp*, **Parts Brand** = *Genuine*, advisor.
4. **Line Items**: add a **Spare** / **Labour** / **Package** line; per line set Group / HSN / Tax,
   **Decision** (Repair/Replace/…), and **Depreciation** category + % → confirm **Net (after dep.)**
   updates.
5. **Status & Follow-up**: set **Status** = *Rejected* → confirm **Rejection Reason** reveals and is
   required; or *Fully Approved* → `approved_at` is stamped (feeds "Approved Today"). Set reminder =
   *Custom* → confirm the days field reveals.
6. Save → back on index. The **Pending Customer / Pending Insurance** tiles are clickable
   authorisation filters; use the **Approved on Date** picker for the custom-date KPI; **Analysis
   Report** exports the filtered CSV.

## Related modules impacted

- **Reused (no changes):** JobCard, SalesEstimate, SurveyorInspection (21), InsuranceCompanyMaster,
  CustomerMaster, CustomerVehicleMaster, WorkshopDepartmentMaster, ServiceTypeMaster, EmployeeMaster,
  CustomerApprovalTypeMaster (approval mode), FollowUpModeMaster, SpareMaster, LabourMaster,
  ServicePackageMaster, InventoryGroupMaster, HsnMaster, TaxMaster.
- New permissions synced; menu under **Insurance**. Reminders are config-only.

## Effect on the system

Closes the insurance chain: an estimate can now be routed for a line-by-line customer/insurer
decision with depreciation applied, and the dashboard surfaces exactly what's pending whose approval.
Additive.

## Design notes

- **Depreciation type** is a new model enum (no depreciation master exists anywhere); percentage is a
  per-line field so slabs can vary by part + vehicle age at data-entry time.
- **Approval mode** reuses CustomerApprovalTypeMaster; **authorisation / status / type / parts brand /
  rejection reason** are model enums. The "Estimate Analysis Report" is the Index's filtered CSV export.
