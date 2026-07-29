# Vehicle AMC — module 80

A vehicle Annual Maintenance Contract: package, validity, included services / spares (with discounts),
usage limit, payment and the active → expired / renewed lifecycle. Uses an FY-based series
`MQ/AMC/26-27/#####`. Header + included-item lines + document attachments.

## What's done

`app/Modules/VehicleAmc/` (`MQ/AMC/<FY>/####`, group **Sales**, route `vehicle-amc.index`)
- Parent `vehicle_amcs` + `vehicle_amc_items` (included service / spare with discount) +
  `vehicle_amc_attachments`.
- Enums: `packages` (Silver / Gold / Platinum), `validities` (12 / 24 / 36 months), `serviceLimits`
  (2 / 3 / 4 / 6 services), `statuses` (active / expired / renewed / lost / cancelled),
  `paymentStatuses` (pending / partially_paid / fully_paid / refunded); item `itemTypes`. Attachment
  types: signed AMC agreement / advisor note / customer note.
- **FY series** `MQ/AMC/<FY>/####` via a persisted `fy_label` (its own per-FY counter,
  `App\Support\FinancialYear`). Carries the AMC amount, start / end dates, **usage limit vs services
  availed** (with a `servicesRemaining()` helper), and a T&Cs text.
- **Included items:** each line is a covered service / spare (auto-fills uom/hsn/tax/description from a
  picked spare) with an optional discount. `end_date` must be ≥ `start_date`.
- **Index**: KPIs (Active AMC / **Renewals Due** (expiring within 7 days, amber) / Services Availed /
  **AMC Revenue** (fully-paid contracts)), CSV **AMC Report** (with used/limit services column).
- Tests: **10 green** (covers the FY-series increment + remaining-services helper).

## How to visually test on the UI

1. **Sales → Vehicle AMC → New AMC.** Pick the **Customer** / **Vehicle** and the **AMC Sold By**
   advisor; choose the **Package**, **Validity**, **Usage Limit**, start / end dates and amount.
2. Add **included services / spares** — flip Spare/Labour, pick a spare (auto-fills), set qty and any
   discount.
3. Set **Status** + **Payment Status**, enter the T&Cs, attach the signed agreement; save — the number
   is `MQ/AMC/<FY>/0001` (increments per financial year).
4. Index shows active / renewals-due (amber) / services-availed / AMC revenue; **AMC Report** exports
   the CSV.

## Related modules impacted

- **Reused (no changes):** Customer + Vehicle, WorkshopDepartment, EmployeeMaster, SpareMaster +
  uom/hsn/tax masters. `App\Support\FinancialYear` for the series.
- New permissions synced; menu under **Sales**. Provides the `vehicle_amcs` table that the AMC service /
  renewal follow-up module (81) references.

## Effect on the system

Adds the AMC product record — packages, coverage, usage ledger and expiry — that feeds the AMC service /
renewal follow-up pipeline (81). Additive — no existing module changed.

## Design note (interpretation — flag)

`services_availed` is a manual counter here (the auto-increment on job-card completion is a cross-module
hook — see module 81's automation notes). The AMC **service / renewal reminder schedules** in the spec
are config-only (not stored as reminder rows), per the no-send policy. The included-item **line total /
package price roll-up** isn't auto-computed — the AMC `amount` is entered directly; wire a computed
total if you want it derived from the items.
