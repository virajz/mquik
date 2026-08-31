# Job Card — rework tracker

Audited. Legend: `[ ]` pending · `[x]` done · `[~]` already in place

## Already in place (verified)
- [x] Service Package & Job Description live in Complaints & Repairs, not Timing & Routing
- [x] Status & Stage are read-only in the form (derived/shown, not typed) — now on the listing too
- [x] Pending Reason on the listing with its "since" date & time
- [x] Customer signature capture exists on the Close-out tab (upload + clear)

## Chunk 1 — Insurance & Authorisation
- [x] 1. Insurance Company + Policy No. show only when a Bodyshop-type department is selected
- [x] 2. Vendor field replaced: advisor picks EITHER an in-house technician OR a service contractor / outside-labour vendor
- [x] 3. Customer Approval Option dropped; digital signature is the approval record (already captured on Close-out — move/surface it here)
- [x] 4. Vehicle State at Receipt moves up, directly after Customer & Vehicle

## Chunk 2 — Service intelligence
- [x] 5. Show last service KM in the odometer label ("Last service: 42,300 km")
- [x] 6. Last Recommended Service popup (from ServiceRecommendationFollowUp for this vehicle)
- [x] 7. Vehicle History searchable by spare/labour, with quick filters: Last PMS, Oil Change, Alignment/Balancing

## Notes
- Spares & labour history comes from `regular_sales_invoice_items` (120k rows, `line_type` = spare|labour), joined through the invoice's `job_card_id`.
- "Bodyshop dept" is matched on the department name containing BODYSHOP — flagged if you'd rather it be an explicit flag on the department master.

## Blade trap found here (applies app-wide)
An inline `@php(...)` that appears BEFORE a `@php ... @endphp` block in the same
file gets swallowed by the block matcher and compiles to a broken `<?php(` with
no terminator — the page dies with "unexpected token". Use the same form
consistently within a file. Swept: no other blade file in the app is affected.
