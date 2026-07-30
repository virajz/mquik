# Service Package (pricing extension) — module 81

The existing `ServicePackageMaster` (Combo / PMS / AMC / New-Vehicle-Care bundle master) was **extended in
place** into a full priced package builder — rather than building a second, overlapping package module. It
keeps its route, permissions and existing service-included child; this batch adds spares, pricing, usage
rules and attachments.

## What's added (extension)

`app/Modules/ServicePackageMaster/` (route `service-package-master.index`, group **Workshop**)
- **Header pricing + rules** on `service_packages`: `usage_rule` (One-Time Use / One Vehicle Only / Multi
  Use), `terms_conditions`, and the package roll-up `net_price` (MRP), `offer_price`, `discount_percent`,
  `saving_price` (profit), `remarks`. (`total_price`, code, type-FK, validity, is_amc, is_active already
  existed.)
- **Per-line pricing on the included services** (`service_package_services`): `sac`, `rate`, `quantity`,
  `tax_percent`, `taxable_value`, `net_price`, `offer_price`, `discount_percent`, `saving_price` — alongside
  the existing service-type + due-after-months/km scheduling.
- **New included-spares child** (`service_package_spares`): spare picker (auto-fills uom/hsn/tax/description),
  HSN/SAC, and the same rate/qty/tax/taxable/net/offer/discount/saving pricing shape. Blank rows are stripped
  before validation; duplicate handling matches the services child.
- **New attachments child** (`service_package_attachments`): package brochure / package note (jpg/png/webp/pdf).
- Package type list (COMBO / PMS / AMC / New Vehicle Care) stays in `ServicePackageTypeMaster`.
- Tests: **18 green** (existing 14 + 4 new: spares+pricing+usage-rule persist, spare auto-fill, blank-spare
  strip, cascade on delete).

## How to visually test on the UI

1. **Workshop → Service Packages → New Service Package.** Set name/code, **Package Type**, **Usage Rule**,
   validity.
2. **Package Pricing**: enter Total (taxable) / Net (MRP) / Offer / Discount % / Saving.
3. **Included Services**: add a row, pick a service type, set the schedule (after months/km) and the per-line
   SAC/rate/qty/tax/offer/saving.
4. **Included Spares**: Add spare → pick a spare (auto-fills), set pricing.
5. **Terms & Files**: T&Cs text + attach a brochure. Save. Reopen to confirm children round-trip.

## Related modules impacted

- **Reused (no changes):** ServicePackageTypeMaster, ServiceTypeMaster, SpareMaster + uom/hsn/tax masters.
- No route/permission changes — the extended module keeps `service_package_master.*` and its Workshop menu
  entry. `VehicleAmc` (80) remains the *sold-contract* record; this is the *catalogue/template* master.

## Effect on the system

The package master now carries full pricing + spares + usage rules, so it can seed a job-card / estimate
package line and drive AMC/combo offers. Purely additive columns + two new child tables; existing data and
the existing service-included child are untouched.

## Design note (interpretation — flag)

Pricing fields are **stored, not auto-computed** — taxable_value / net / saving are entered, not derived from
rate × qty × tax (wire a computed roll-up if you want the header to sum its lines). Decision logged with the
user: **extend the existing master** rather than build a duplicate Service Package module.
