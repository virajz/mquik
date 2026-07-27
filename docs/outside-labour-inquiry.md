# Outside Labour Inquiry (OLI) — module 12

Request-for-quote to an outside contractor/vendor for work the workshop doesn't do in-house
(denting, painting, upholstery, etc.). Tracks the inquiry lifecycle, promised turnaround,
follow-up config, and evidence/attachments.

## What's done

**Transactional module** `app/Modules/OutsideLabourInquiry/`
- Parent `OutsideLabourInquiry` (`outside_labour_inquiries`), auto-numbered `OLI-#####`.
- Children: `OutsideLabourInquiryScope` (labour / job-description / complaint + description per line)
  and `OutsideLabourInquiryAttachment` (typed evidence photo **or** PDF/image document).
- Dedicated create/edit **page** (not a modal): `/outside-labour-inquiry/create` and `/{id}/edit`.
- Fields: inquiry type, department, raised-by employee, job card, customer, vehicle, vendor,
  HSN, tax, priority, communication mode (WhatsApp/Email/Phone), TAT (1/2/3 days or custom),
  promised from/to, revision reason, rejection reason, 6-state status, reminder frequency
  (daily/every-2-days/custom) + follow-up mode + notification template stage, notes.
- **Reminders/notifications are config-only — nothing is sent.**
- Index with 4-tile dashboard (Open / Pending Responses / Completed / Cancelled), search,
  status/type/vendor filters, server-side searched vendor picker, delete.

**New master** `OutsideLabourRejectionReasonMaster` (`outside_labour_rejection_reasons`) —
High Cost, Delay in Delivery, Poor Quality History, Service Provider Not Available, Capacity Full,
Out of Scope. Full CRUD + import/export, seeded, menu under **Workshop**.

**Report** `OutsideLabourStatusReport` (`/outside-labour-status-report`) — filterable
(status/type/vendor/date range) read-only screen + CSV export.

Tests: OLI 16, rejection-reason master 10, status report 6 — all green.

## How to visually test on the UI

1. Go to **Workshop → Outside Labour Inquiries** → **New Inquiry**.
2. Pick an **Inquiry Type** (Denting/Painting/…), set **Priority**.
3. Under **Vehicle & Contractor**, search-pick a **Vendor**, **Customer**, **Vehicle**, **Job Card**,
   and an **HSN/SAC** + **Tax Rate**.
4. Add one or more **Work Scope** rows (labour/job-description/complaint + a description — description required).
5. **Turnaround & Follow-up**: choose TAT = *Custom* → confirm the custom-days field reveals;
   set promised from/to; pick a reminder frequency = *Custom* → confirm its days field reveals.
6. **Photos & Attachments**: Add file → upload a PDF or image; pick an evidence type.
7. **Status**: set to *Rejected by Contractor* → confirm the **Rejection Reason** field reveals and
   is required on save.
8. Save → you land back on the index; the new row shows an `OLI-#####` number. The dashboard tiles
   are clickable status filters.
9. **Workshop → Outside Labour Report** → apply filters → **Export CSV**.

## Related modules impacted

- **Reused (no changes):** WorkshopDepartmentMaster, EmployeeMaster, JobCard, CustomerMaster,
  CustomerVehicleMaster, VendorMaster, HsnMaster, TaxMaster, PriorityMaster, FollowUpModeMaster,
  LabourMaster, JobDescriptionMaster, ComplaintTypeMaster, PhotoTypeMaster.
- **Seeders extended:** `EstimateRevisionReasonMaster` (+ `SCOPE CHANGE`),
  `ServiceSpecialistMaster` (+ Repowering, Alloy/Rim Refurbish, Accessories Installation, Lathe Work —
  this is the reused "Inquiry Type" catalogue, already linked to VendorMaster service specialities).
- **DatabaseSeeder:** registered `OutsideLabourRejectionReasonMasterSeeder`.
- Permissions registered via `php artisan auth:sync-permissions`.

## Effect on the system

Gives the workshop a first-class RFQ workflow for outsourced work, linking a job card + customer
vehicle to an outside vendor with a promised date and an auditable status trail. Reuses the vendor
"service speciality" catalogue as the inquiry-type dimension, so vendor↔work-category data now feeds
inquiry routing. No existing screens changed behaviour; this is additive.
