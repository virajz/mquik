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

---

# Legacy spare-master import (2026-08-04)

The client's previous-ERP spare export (`Spare Master data upload.csv`, 30,635 rows) is now imported by
`MasterDataSeeder::seedSpares()` from `database/seeders/data/master-data/Spare.csv`.

## Result

**29,680 spares + 30,635 dated rate revisions.** Coverage: 29,661 have an HSN, 29,680 a tax slab, 29,667 a
UoM, 29,681 an inventory group, 29,673 a workshop department, 23,914 a part number.

Masters auto-created from the data: **948 HSN codes** (965 total), **4 UoMs** (SQF/SQM/ROL/CYL), **4 spare
brands** (773 total). Inventory groups/sub-groups were already seeded from `InvGroup`/`InvSubGroup`.

## Rate history — new

The old ERP stored one row per price revision (its `WEF` "with effect from" column), all sharing a part
number. That can't live on `spares`, which holds a single current rate — so:

- **`spare_rate_history`** (migration `2026_08_04_090000`) — `spare_id`, `spare_brand_id`, `rate_before_tax`,
  `mrp`, `effective_from`, `source` (`legacy_import` / `manual`), `remark`.
- Rows sharing a natural key collapse into **one spare carrying the newest revision**; every revision
  (including the newest) is kept as history. e.g. part `48068-0D081` — ₹1,540.62 (Feb 2021) → ₹1,568.36
  (Feb 2022) → ₹1,773.44 (Jul 2025).
- The Spare edit page gained a read-only **Rate History** section (date, rate, MRP, % change vs. the previous
  revision, brand), shown only when history exists.
- Saving a spare with a changed rate now writes a `manual` revision dated today, so the trail keeps growing.
- **`spares.legacy_key`** (migration `2026_08_04_090100`) — sha1 of the natural key; makes the import
  idempotent and matches history rows back to their spare without relying on insert order.

## Column mapping

| CSV | → | Note |
|---|---|---|
| PartName / PartDescription / PartNo | name / description / spare_code | uppercased |
| HSNACSNo | hsn_id | digits ≤ 8 only; created if missing, GST% from `Vat` |
| Company | spare_brand_id | created if missing |
| UOM | uom_id | code match (PCS→PIECES, SET→SETS, LTR→LITRES, KGS/KG→KILOGRAMS…) |
| Rate / TotalAmount | rate_before_tax / mrp | verified: TotalAmount = Rate × 1.18 at Vat 9 |
| Vat | tax_id | half-rate: 9→GST 18%, 2.5→GST 5%, 0→NIL RATED |
| InvGroupName / InvSubGroupName | inventory_group_id / inventory_sub_group_id | |
| DepartmentName | **inventory_type** | exact 7-for-7 match with the enum |
| MainDepartmentName | workshop_department_id | Mechanical→SERVICE, Value Addition→DETAILING (mapped, not duplicated) |
| DepartmentName | spare_type | TYRES→tyre; CONSUMABLES/LUBRICANTS→common; else vehicle_specific |
| WEF | spare_rate_history.effective_from | d/m/y; 30,444 parsed, 191 blank |
| Remarks | remark | |

**Dropped** (no column / junk): MadeIn (98% blank), VINNo, InventoryName (concatenated display string),
Service (duplicates Vat), AVat (all 0), IsMultiBarCode, SpareCategory, SPCategory (FAST/NON MOVING — no
column exists), VendorBarcode (all NULL), Location (10 non-blank, all `undefined`/`0`/`'`), Ref_PartNo (99%
NULL), ReorderQty/MaxQty (all 0).

## Dirty-data handling

- **585 part numbers are reused for unrelated parts** (`F002H50028` = both ADBLUE and BATTERY DIN-60 S5).
  `spare_code` is UNIQUE, so the first claimant keeps it and the rest import with `spare_code = null` plus
  `LEGACY PART NO: <x>` in `remark`. No spare is dropped.
- **5,727 rows have no part number** — keyed on name + brand + HSN + sub-group instead.
- 2 part numbers over 64 chars are comma-joined vendor lists → dropped to null.
- 19 HSN values are 9–10 digits or stray prices → `hsn_id` null, and no junk HSN master row created.
- `NULL` / `undefined` string sentinels collapse to null everywhere.

## How to visually test on the UI

1. **Inventory → Spares** — 29,681 rows; search `48068-0D081`.
2. Open it: rate ₹1,773.44, Inventory Type *Mechanical*, group SUSPENSION / LOWER ARM, dept SERVICE.
3. Scroll to **Rate History** — three revisions newest-first with +13.1% / +1.8% change badges.
4. Change the rate and save, reopen — a new revision dated today appears on top.

## Related modules impacted

- **Masters grown by the import:** HsnMaster (+948), UnitOfMeasureMaster (+4), SpareBrandMaster (+4).
- **Reused unchanged:** InventoryGroupMaster, WorkshopDepartmentMaster, TaxMaster.
- **Not touched:** `SpareImporter` / `SpareExporter` (the CSV wizard) — they carry neither `inventory_type`
  nor rate history. Extend them if bulk round-tripping either matters.
- `part_type_id`, `rack_id`, `location`, barcode and vehicle-compatibility stay null — the source has no
  such data. ~28,700 vehicle-specific spares therefore show an empty compatibility list until it's filled in.

## Tests

`tests/Feature/Modules/SpareMasterLegacyImportTest.php` — 7 tests over a fixture CSV (collapse + newest-wins,
part-no collision, blank-part-no keying, classification mapping, master auto-creation, junk rejection,
idempotency). `SpareMasterTest.php` grew 3 (manual revision on rate change, history rendered newest-first,
section hidden when empty). **37 green.**

## Running it on a server

```bash
php artisan migrate --force                     # spare_rate_history + spares.legacy_key
php -d memory_limit=512M artisan import:spares  # CSV ships in the repo
```

`import:spares` runs only this step — no need for the full `MasterDataSeeder` chain. Pass `--path=/some/dir`
when the CSV was uploaded outside the repo (the file must be named `Spare.csv`). Idempotent: re-running
imports nothing twice, so an interrupted run is safe to repeat.

Peak memory is ~82 MB for the CSV alone (it is grouped in memory, not streamed) — closer to ~200 MB with the
framework booted, so raise `memory_limit` if the server default is 128 M. The run takes ~10 s.

---

# Search: multi-token across name + description (2026-08-04)

The index page hand-rolled its own single-term `LIKE` over name / part no / HSN — it never looked at
`description`, and a two-word query was treated as one literal string. It now uses the shared
`Searchable::scopeSearch()` that Customers already uses:

**each token must match somewhere across name / part no / description, and every token must match.**

- `absorber laura` → **ABSORBER SHOCK RR** (`description`: PASSAT, JETTA, **LAURA**, SUPERB, YETI),
  ABSORBER SHOCK RR L/R, SUPPORT SHOCK ABSORBER RR. This matters because the legacy import puts the
  **applicable vehicles in `description`** — 27,015 spares carry one, 240 mention LAURA.
- `absorber laura yeti` → drops rows where YETI appears nowhere.
- Part no and HSN still work: HSN lives on a relation, so it is OR'd in alongside the token group.
- On Postgres, tokens of 4+ chars also match by trigram similarity, so `absrober` still finds ABSORBER.

`spares` had **no trigram indexes** — `auth:sync-search-indexes` had not been re-run since the module became
searchable. Running it created `spares_name_trgm_idx`, `spares_spare_code_trgm_idx`,
`spares_description_trgm_idx` (242 columns covered across all modules).

**Run this on the server after deploying:**

```bash
php artisan auth:sync-search-indexes   # idempotent, Postgres-only, safe to re-run
```

Timings on the real 29,681-row table: `absorber laura` 3 matches / ~165 ms, `brake` 2,419 matches / ~100 ms,
`bmw brake pad` 190 matches / ~4 ms. The fuzzy `word_similarity(...) > 0.4` clause is the slow part — it
cannot use the GIN index (the index-accelerated form is the `<%` operator plus a session
`pg_trgm.word_similarity_threshold`). Worth revisiting in `Searchable` if search ever feels sluggish; it
would speed up every module, not just spares.

Tests: 3 added to `SpareMasterTest.php` (multi-token across name+description, every-token-must-match, part
no + HSN still resolve). **33 green** there, plus MasterSearch + the searchable-fields audit unaffected.
