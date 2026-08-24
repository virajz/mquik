# Merging Requested Repairs into Job Descriptions — migration plan

`RequestedRepairMaster` and `JobDescriptionMaster` describe the same thing: a job the workshop can do.
The plan is to keep job descriptions and retire requested repairs. This is a data merge, not a module
delete — the pivot alone carries **26,734 rows across 9,489 job cards**.

## What is actually there

| | Requested Repairs | Job Descriptions |
|---|---|---|
| Rows | 50 | 15 |
| Department link | `requested_repair_workshop_department` pivot (**many-to-many**) | via `service_type_id` → `service_types.workshop_department_id` (**one**) |
| Used on job cards | `job_card_requested_repair`, 26,734 rows | `job_cards.job_description_id`, **1 row set** |
| Category | none | `frequent` / `general` |
| Other fields | `code`, `notes` | `code`, `notes`, `standard_hours` |

Consumers — 16 files across 5 modules:

- **JobCard** — the deep one. 14 references in `Livewire/Edit.php` (`requestedRepairIds`, validation,
  `requestedRepairOptions()`, department re-scoping, `sync()`), the `requestedRepairs()` BelongsToMany
  on the model, and `requestedRepairs.name` inside `$searchableFields`.
- **ShowsServiceHistory** — reads `requestedRepairs` to build the vehicle's service history. Renaming
  this silently changes what the advisor's history screen shows, so it needs testing, not just a swap.
- **VehicleInspectionOrder** — `requested_repair_id` on scopes, a picker, validation, save.
- **TechnicianBench** — one eager-load, `requestedRepair:id,name`.
- **ImportLegacyTransactions** — maps legacy `Repairs.csv` → `requested_repairs` and writes the pivot.
- Plus: 6 permissions, an Exporter, an Importer, a seeder, `MasterDataSeeder`, `DatabaseSeeder`, and
  `RequestedRepairMasterTest`.

**The good news:** `vehicle_inspection_order_scopes`, `final_work_order_scopes`,
`outside_labour_inquiry_scopes` and `outside_labour_order_scopes` **already carry a
`job_description_id` column**, and **0 rows** currently use `requested_repair_id`. That whole branch is
a column swap with no data to move.

## Four decisions needed before any code

**1. Which service type does each of the 50 land under?**

A job description hangs off a *service type*, not a department — that is how it derives its department.
Requested repairs only know their department. So each of the 50 needs a service type chosen inside its
department:

```
SERVICE      25 repairs  → FREE SERVICE / GENERAL REPAIR / PAID SERVICE / RUNNING REPAIR
BODYSHOP     14 repairs  → BODYSHOP INSURANCE / BODYSHOP REPAIR
DETAILING    10 repairs  → CERAMIC COATING / DETAILING
TYRE          5 repairs  → TYRE REPLACEMENT / WHEEL ALIGNMENT
ACCESSORIES   1 repair   → ACCESSORIES FITMENT
```

Nothing maps to INSURANCE or VALUE ADDITION, so there are no orphans.

- **(a) Map by hand** — a 50-row CSV, one afternoon, and the result is correct. *Recommended.*
- **(b) One catch-all service type per department** — e.g. "GENERAL REPAIR" as the default. Fast, but
  every migrated job lands in a bucket somebody will have to re-sort later.
- **(c) Leave `service_type_id` null** — the column allows it. They keep working everywhere they are
  referenced today, but they will not appear in the appointment checklist until assigned.

**2. The 5 repairs that belong to two departments.**

`WHEEL BRG CHECK`, `WHEEL BRG CHECK - RR` (SERVICE + TYRE), `WATER LEAKAGE CHECK` (SERVICE + BODYSHOP),
`HEADLAMP RESTORATION`, `PAINT RESTORATION` (BODYSHOP + DETAILING).

A job description has exactly one. Either **split into two rows** (the unique index is on
`(service_type_id, name)`, so the same name under two service types is legal — but then the 26,734
pivot rows need a rule to pick which of the two they point at), or **pick one department** and accept
the loss. With 5 items, picking is likely simpler.

**3. The 2 name collisions** — `WHEEL ALIGNMENT` and `AC GAS REFILL` already exist as job descriptions.
Merge into the existing row (repoint their pivot rows) or keep both under different service types.

**4. Category for the migrated 50** — default them to `general` so they land in the appointment's
Requested Repairs dropdown rather than the checklist, and promote the genuinely common ones to
`frequent` afterwards. Migrating them all as `frequent` would put 50 checkboxes on the booking screen.

## Migration phases

Each phase is independently deployable. Nothing is dropped until the last one.

### Phase 1 — create the job descriptions (additive, reversible)

A one-off command, `php artisan repairs:merge-to-job-descriptions --dry-run`, that reads the mapping
CSV from decision 1 and, for each requested repair, `firstOrCreate`s a job description on
`(service_type_id, name)` carrying `code`, `notes`, `is_active`, and `category = 'general'`.

It writes a `requested_repair_id → job_description_id` map to a table (or a stamped CSV), which every
later phase reads. **Nothing else changes yet** — both masters are live.

### Phase 2 — repoint the scope tables (trivial)

`vehicle_inspection_order_scopes`, `final_work_order_scopes`, `outside_labour_inquiry_scopes`,
`outside_labour_order_scopes` already have `job_description_id` and **0 rows** use
`requested_repair_id`. Swap the picker, validation and save in `VehicleInspectionOrder/Livewire/Edit.php`
and the relation on `VehicleInspectionOrderScope`, and fix the one eager-load in TechnicianBench.
No data migration at all.

### Phase 3 — the job-card pivot (the real one)

1. Create `job_card_job_description` (`job_card_id`, `job_description_id`, unique on the pair).
2. Backfill from `job_card_requested_repair` through the Phase 1 map, inside a transaction.
   **Watch the row count:** if two requested repairs merged into one job description, 26,734 rows will
   collapse to fewer — that is correct, but verify the drop matches the number of merges rather than
   assuming.
3. Point `JobCard::requestedRepairs()` at the new table, renamed `jobDescriptions()`.
4. Update `$searchableFields` (`requestedRepairs.name` → `jobDescriptions.name`).
5. Update `ShowsServiceHistory` — and **re-check the service history screen**, since this is what feeds
   the advisor's "when did we last do X" list.
6. Update `JobCard/Livewire/Edit.php`: rename `requestedRepairIds`, repoint `requestedRepairOptions()`
   at job descriptions filtered by department **through service type**, and update the `sync()`.

Keep the old pivot table in place, still populated, until Phase 5.

### Phase 4 — retire the module surface

Remove routes, menu entry, Livewire Index/Form and views, Exporter, Importer, factory, seeder, and the
`RequestedRepairMaster` model. Drop the 6 permissions via `auth:sync-permissions`. Delete
`RequestedRepairMasterTest` and update `MasterDataSeeder` / `DatabaseSeeder`.

Point `ImportLegacyTransactions` at job descriptions so legacy re-imports keep working — this one is
easy to forget, and it fails silently until the next import.

### Phase 5 — drop the tables (irreversible)

After a bake period with the new pivot in production: drop `job_card_requested_repair`,
`requested_repair_workshop_department`, `requested_repairs`, and the now-unused `requested_repair_id`
columns on the four scope tables.

**Take a database dump before this phase.** Everything up to here is reversible; this is not.

## Testing

Tests that must be green before Phase 5:

- `JobCardTest` — repairs selected, saved, re-loaded, department re-scoping
- `AppointmentTest` — the checklist and Requested Repairs dropdown
- Service history — a vehicle whose past cards used requested repairs still shows those services
- VIO scopes — a scope saved against a job description
- A migration test asserting the pivot row count before and after the backfill

## Effort

Phase 1 is an afternoon plus the mapping decisions. Phase 2 is an hour. Phase 3 is the bulk — a day,
mostly in `JobCard/Livewire/Edit.php` and re-verifying service history. Phases 4 and 5 are half a day
between them, separated by however long you want to bake.
