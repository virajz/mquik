# Spares Master (inventory-type + attachments) — module 98

The existing `SpareMaster` already covered almost the whole spec, so this was a **small extend-what's-new**
pass: it adds the fixed **Inventory Type** classification and a **document-attachments** child (Spares Image /
Application Guide). Nothing else was rebuilt — the working UI and all 24 prior tests are untouched.

## Already present (mapped, not rebuilt)

- **Application Type** (Vehicle Specific / Tyre / Common) → existing `spare_type` (segmented control) with
  reveal logic: tyre fields for Tyre, vehicle-compatibility for Vehicle Specific.
- **Vehicle Compatibility** (Make / Model / Variant, search + multi-select) → existing `vehicleVariants`
  (`spare_vehicle_variants` pivot) with a server-backed brand/model/variant picker.
- **Spares Type** (After Market / Genuine / OEM / Refurbished) → existing `part_type_id` → PartTypeMaster
  (which holds exactly those four).
- **Spares Brand** → `spare_brand_id`; **Department** → `workshop_department_id`; **Inventory Group /
  Sub-Group** → `inventory_group_id` / `inventory_sub_group_id`; **Location / Rack / Bin** → `rack_id` +
  `location`; **UOM** → `uom_id`; **HSN & Tax** → `hsn_id` + `tax_id`.

## What's added (extension)

`app/Modules/SpareMaster/` (route `spare-master.index`, group **Inventory**)
- **`inventory_type`** — fixed classification (Accessories / Body Parts / Consumables / Lubricants /
  Mechanical / Tyres / Wheel Rim & Parts), distinct from the hierarchical group / sub-group.
- **`spare_attachments`** child — Spares Image / Application Guide (jpg/png/webp/pdf); files are removed from
  storage when the spare is deleted.
- Tests: **27 green** (24 existing preserved + 3 new: inventory-type persist, unknown-type rejected,
  attachment stored).

## How to visually test on the UI

1. **Inventory → Spares → New Spare.** Fill name/code; the **Part Type** segmented control (Application Type)
   still reveals tyre fields / vehicle-compatibility as before.
2. In the classification block, pick an **Inventory Type** (e.g. *Lubricants*) alongside the existing
   Inventory Group / Sub-Group.
3. New **Documents** section → **Add file**, choose *Spares Image* or *Application Guide*, upload; save and
   reopen to confirm it round-trips.

## Related modules impacted

- **Reused (no changes):** PartTypeMaster, SpareBrandMaster, InventoryGroupMaster, WorkshopDepartmentMaster,
  RackMaster, UnitOfMeasureMaster, HsnMaster, TaxMaster, VehicleVariantMaster.
- **No route/permission changes** — same `spare_master.*` perms and Inventory menu entry. Import/export
  untouched.

## Effect on the system

Adds the inventory-type classification and spare documents additively — one new column + one new child
table. Existing spares, the vehicle-compatibility pivot and the price/tax logic are unchanged.

## Design note (interpretation — flag)

- **Inventory Type** is modelled as a fixed enum (matching the spec's seven values), separate from the
  hierarchical Inventory Group / Sub-Group — the spec lists all three as distinct fields.
- The spec's "Spares Type" (After Market / Genuine / OEM / Refurbished) maps to the existing **PartType**
  master rather than a new column — those are the exact four rows already seeded, so no duplication.
- Attachments allow multiple files per type; the importer/exporter were not extended to carry the new
  `inventory_type` column or attachments (add to `SpareImporter`/`SpareExporter` if bulk round-tripping them
  matters).
