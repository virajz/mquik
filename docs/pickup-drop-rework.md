# Pickup & Drop — rework tracker

Sequential after Appointment. Reuse Appointment's patterns wherever one exists
(derived status, region pickers, quick-service checklist, filter popover). An
appointment is NOT required for a pickup/drop — walk-in jobs stay first-class.

Legend: `[ ]` pending · `[x]` done · `[~]` partially done / needs decision

## Chunk 1 — Booking form basics
- [x] 1. Remove "This Job Is" — derive the leg from the Pickup/Drop Type dropdown
- [x] 2. Time Slot shows the wrong value (drop jobs get the appointment's *pickup* slot; scheduled time can disagree with the slot window)
- [x] 3. Rename "Date" → "Entry Date", "Time" → "Entry Time"
- [x] 4. Pending Reason — keep editable on form, show as a listing column (same resolution as Appointment)
- [x] 5. Quick add (+) for Customer, Customer Vehicle, Pending Reason, Reschedule Reason

## Chunk 2 — Addresses & mandatory fields
- [x] 6. Enable/disable Pickup / Drop Address per the chosen type, with region (state/city/area) pickers — reuse Appointment's `PicksAddressRegions`
- [x] 7. Distance Slab & Charge auto from Distance (KM) — already live in this module (Appointment copied it from here)
- [x] 8. Mandatory: Pickup/Drop Type, Date, Time, Time Slot, Customer, Vehicle, Pickup Address (per type), Drop Address (per type), Department, Service Type, Advisor, Customer Complaints

## Chunk 3 — Assignment & Routing
- [x] 9. Field sequence: 1st Department, 2nd Service Type, 3rd Advisor
- [x] 10. Advisor — only advisors, filtered by chosen department
- [x] 11. Service Type — filtered by chosen department (reuse Appointment's rule)

## Chunk 4 — Status & assignment history
- [x] 12. Status auto-derived, not typed (same doctrine as Appointment)
- [x] 13. Driver & Vendor assignment history: assigned at / by / to, departed time, reached-location time
- [x] 14. Driver-wise lists: assigned vehicles, collected vehicles, collection pending

## Chunk 5 — Customer Complaints
- [ ] 15. Dept-wise quick-add services (frequent job descriptions) — reuse Appointment's checklist
- [ ] 16. Complaint Type dropdown removed — direct complaint add
- [ ] 17. Job Description dropdown removed — quick-add covers it

## Chunk 6 — Driver leg (checklist, photos, OTP)
- [ ] 18. Document Checklist: only DOCUMENT COLLECTION TEMPLATE, remove the rest
- [ ] 19. Checklist is the driver's job at the pickup location — driver captures selfie & vehicle images
- [ ] 20. Vehicle Condition: View & When fields get overridden when selecting view then uploading — fix; driver-side
- [ ] 21. OTP send & verify (auto-generated) — reuse the mocked OtpService

## Chunk 7 — Listing
- [ ] 22. Default to all Pending pickups & drops on load
- [ ] 23. Easy search (space or %) customer+vehicle / vehicle+reg — shared Searchable already upgraded; widen this model's fields
- [ ] 24. New filters: created date, time slot, Pickup/Drop Type, job card no.
- [ ] 25. Columns: created date, Vehicle, Department, Advisor, Time Slot, Pending Reason, Job Card No.
- [ ] 26. Cancel action with standard reason (Flux modal, no browser alert)
