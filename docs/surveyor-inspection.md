# Surveyor Inspection — module 21

Record an insurance surveyor's findings against a claim/estimate: survey type, per-line
repair/replace decisions, overall approval, and not-covered / rejection reasons.

## What's done

**New module** `app/Modules/SurveyorInspection/` (`SI-#####`).
- Parent `surveyor_inspections`: job card, **claim intimation** link, **sales estimate** reference,
  customer, vehicle, insurance company; **surveyor name + phone** (surveyors are external insurer
  staff — captured as name/phone, not employees); **survey type** (Preliminary / Re-inspection /
  Final / Spot / Supplementary); **surveyor approval** (Repair / Replace / Not Approved / Partial /
  Total Loss); **not-covered reason** (Old Damage / Damage Mismatch / Not in Policy); **rejection
  reason** (Document Missing / Policy Expired / Policy Not Valid / Fraud / Insufficient Evidence);
  status (Pending / In Progress / Completed / Cancelled); `surveyed_at` (required + revealed when
  Completed).
- Line items `surveyor_inspection_items` — spare/labour with a **per-line decision** (Repair /
  Replace / Remove & Refit / Approved / Rejected / Approval Pending).
- Attachments `surveyor_inspection_attachments` — approval note / survey photo (PDF/image).
- Index with 4-tile dashboard, search, status + survey-type + insurer filters, and the **Surveyor
  Inspection Report** (CSV export).

Tests: 12 green.

## How to visually test on the UI

1. **Insurance → Surveyor Inspection → New Inspection.**
2. **References**: link a Job Card, an **Estimate**, and pick the **Insurance Company**.
3. **Surveyor & Type**: enter surveyor name/phone, pick **Survey Type** = *Final* and **Surveyor
   Approval** = *Partially Approved*.
4. **Assessed Lines**: add a Spare and a Labour line; per line pick a **Decision** (Repair/Replace/…).
5. **Outcome & Status**: optionally set Not-Covered / Rejection reason; set Status = *Completed* →
   confirm **Surveyed At** reveals and is required.
6. **Attachments**: Add file → upload the approval note (PDF).
7. Save → back on index with an `SI-#####` row; **Report** exports the filtered CSV.

## Related modules impacted

- **Reused (no changes):** JobCard, ClaimIntimation (module 20), SalesEstimate, CustomerMaster,
  CustomerVehicleMaster, InsuranceCompanyMaster, SpareMaster, LabourMaster.
- New permissions synced; menu under **Insurance**.

## Effect on the system

Links a surveyor's assessment to the claim (module 20) and the estimate, with an auditable per-line
decision trail — feeding the Estimate Approval step (23). Additive.

## Design notes

- Surveyors are **not** modelled as a master/role (none exists and they're external) — captured as
  name + phone on the inspection.
- Survey type / approval / not-covered / rejection are model enums; the "Surveyor Inspection Report"
  is the Index's filtered CSV export.
