# Sales Estimate Template (Master) — module 19

Reusable, priced estimate templates that pre-fill a Sales Estimate. **Extended** the existing thin
`EstimateTemplateMaster` (which only held name/code/group + a spare/labour line list) up to the
full row-19 spec.

## What's done

**Extended master** `app/Modules/EstimateTemplateMaster/`
- Header (`estimate_templates`) added: **effective date**, **template category** (PMS / Brake /
  Suspension / Clutch / Denting / Painting), **vehicle brand / model / variant** (cascading pickers),
  **service / combo / AMC package** link, and a **template brochure** attachment (single file,
  PDF/image).
- Line items (`estimate_template_items`) added: **inventory group**, **UOM**, **HSN**, **tax**,
  free-text **description**, and **unit rate** — so a template now carries pricing, not just a parts
  list. Qty already existed. Total / Tax Amount / Net Sales Amount are derived
  (`lineTotal()` / `taxAmount()` / `netAmount()` on the item model) and shown live per line in the
  editor.
- Index shows the category badge + effective date.
- **Template History** is covered by the module's existing `Auditable` audit trail (every create/
  update is recorded); no separate screen.

Tests: 7 green (existing 4 + new: header fields & priced-line math, brochure upload/clear, category
validation).

## How to visually test on the UI

1. **Masters → Estimate Templates → New Template** (route `estimate-template-master.create`).
2. Set **Template Name/Code**, **Effective Date**, **Category** = *Brake*, pick **Inventory Group**.
3. Pick **Vehicle Brand** → confirm **Model** narrows to that brand → pick **Variant**; optionally a
   **Service/Combo/AMC Package**.
4. Add a **Spare** and a **Labour** line. On a line set Group / UOM / HSN / **Tax** and **Qty** +
   **Rate** → confirm **Total / Tax / Net** update live.
5. Upload a **Template Brochure** (PDF) → save → reopen → confirm it shows, then **×** to clear.
6. Save → back on index; the row shows the category badge and "w.e.f." date.

## Related modules impacted

- **Reused (no changes):** InventoryGroupMaster, SpareMaster, LabourMaster, UnitOfMeasureMaster,
  HsnMaster, TaxMaster, VehicleBrandMaster, VehicleModelMaster, VehicleVariantMaster,
  ServicePackageMaster.
- **Schema:** additive columns on `estimate_templates` + `estimate_template_items` (no data
  migration needed). `SalesEstimate.estimate_template_id` continues to FK into this table unchanged.

## Effect on the system

Templates graduate from a bare parts list to a full priced, vehicle-scoped, categorised bundle with
a brochure — so applying a template to a Sales Estimate can carry rate/tax/HSN/group defaults, not
just line stubs. Additive; the Sales Estimate → template link is untouched.

## Design note

Template **category** is a model enum (fixed workshop set), not a new master. Vehicle applicability
uses the real brand→model→variant masters with cascading pickers. "Template History" leans on the
existing Auditable log rather than a bespoke history table.
