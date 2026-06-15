# Job Card — Masters & Submasters Gap Report

> **Purpose:** Compare the client's required Job Card masters/submasters (two sheets) against what currently exists in the Mquik system, and flag every gap.
> **Date:** 2026-06-03
> **Method:** Verified against the **live database schema** (`Schema::getColumnListing`) + the `app/Modules/` directory, not just migrations. (Migrations alone misled an earlier draft — a `down()` method and a column that moved tables — so columns were confirmed against the running DB.)

---

## Legend

| Symbol | Meaning |
|---|---|
| ✅ | Present as a dedicated, configurable master and (where relevant) wired into the Job Card |
| ⚠️ | Partially present — exists but not a master, not linked, not wired, or incomplete vs spec |
| ❌ | Genuinely missing — no master and no field |

---

## 1. Sheet 1 vs Sheet 2 — are they different?

They describe the **same set of masters**. Differences are cosmetic only:

- **Photo Type framing:** Sheet 1 folds *Customer Approval* into Photo Type examples and adds *"Before Service / After Service"*. Sheet 2 separates *Customer Approval Option* as its own line item.
- **Typo:** Sheet 2 abbreviates *VIN* as *"VI"*.

No master appears in one sheet but not the other. The rest of this report treats them as a single combined requirement list.

---

## 2. Full status — every required master

| # | Required (from sheets) | Status | How / where it lives today |
|---|---|---|---|
| 1 | **Customer** | ✅ | `CustomerMaster`, wired into Job Card |
| 1a | — State / City / Area / Zip Code | ✅ | `RegionMaster` — one polymorphic master (`kind = state \| city \| area \| pincode`, parent hierarchy); customer addresses link via `region_id` |
| 1b | — Customer Type | ✅ | Configurable master `BusinessTypeMaster` (table `business_types`), linked via `customers.business_type_id`, wired into the customer form (combobox "Customer Type" + inline quick-add) + index filter & badge. **FIXED 2026-06-03:** all user-facing labels relabelled from "Business Types" → **"Customer Type"** (internal table/route/permission unchanged). |
| 1c | — GST Type | ✅ | **FIXED 2026-06-03:** added `customers.gst_type_id` FK → `gst_types` (nullOnDelete), `gstType()` relation, and a **GST Type** select in the customer form. Master `GstTypeMaster` seeded COMPOSITION/REGULAR/UNREGISTERED/SEZ/EXPORT/OVERSEAS. |
| 2 | **Customer Vehicle** | ✅ | `CustomerVehicleMaster`, wired into Job Card |
| 2a | — Brand / Model / Variant / Segment / Colour | ✅ | `VehicleBrandMaster`, `VehicleModelMaster`, `VehicleVariantMaster`, `VehicleSegmentMaster`, `VehicleColorMaster` |
| 2b | — VIN | ✅ | Field `customer_vehicles.vin` (plus `registration_no`) |
| 2c | — Fuel Type | ✅ | **FIXED 2026-06-03:** new `FuelTypeMaster` (PETROL/DIESEL/CNG/ELECTRIC/HYBRID/LPG). Legacy enum `vehicle_variants.fuel_type` replaced by `fuel_type_id` FK (backfilled, old column dropped); wired into variant form, index, export/import, and the quick-add-vehicle modal. |
| 2d | — Transmission Type | ✅ | **FIXED 2026-06-03:** new `TransmissionTypeMaster` (MANUAL/AUTOMATIC/AMT/CVT/DCT/IMT). Legacy enum `vehicle_variants.transmission` replaced by `transmission_type_id` FK (backfilled, old column dropped); wired everywhere variants are edited/displayed. |
| 2e | — Vehicle Type (Hatchback / Sedan / SUV / Luxury) | ✅ | **FIXED 2026-06-03:** `VehicleSegmentMaster` relabelled to **"Vehicle Type"** (one body-style master, decision: not split from Segment); **LUXURY** added to the seed. Linked via `vehicle_models.vehicle_segment_id`. |
| 2f | — Registration Type | ✅ | **FIXED 2026-06-03:** new `RegistrationTypeMaster` (Private/Commercial/Government/BH Series/Military/Other). Legacy `number_plate_type` enum replaced by `registration_type_id` FK (backfilled, old column dropped); wired as the "Plate Type" picker. |
| 3 | **Damage Type** (Scratch/Dent/Crack/Rust/Broken/Paint Fade) | ✅ | `DamageTypeMaster` — wired into the inventory tri-state |
| 4 | **Department** | ✅ | **Decision:** Job Card uses `WorkshopDepartmentMaster`; `DepartmentMaster` is the HR org-unit master (separate concern, by design). Both are intentional. |
| 5 | **Service Type** | ✅ | `ServiceTypeMaster`, wired |
| 6 | **Insurance Company** | ✅ | Master `InsuranceCompanyMaster` exists. **Decision:** consumed by downstream Insurance / Bodyshop / claim transactions, not the intake Job Card form (kept lean). |
| 7 | **Employee** | ✅ | `EmployeeMaster`, wired (advisor / technician) |
| 8 | **Vendor + Vendor Type** | ✅ | `VendorMaster` + `VendorTypeMaster` exist. **Decision:** consumed by the Outside-Work / procurement transactions, not the intake Job Card form. |
| 9 | **Inventory Inside Vehicle** | ✅ | `VehicleInventoryItemMaster`, wired (tri-state present/missing/damaged) |
| 10 | **Complaint / Job Type / Job Description** | ✅ | `ComplaintTypeMaster` wired into the Job Card; `JobDescriptionMaster` is the frequent-jobs master. "Job Type" (Mechanical/Bodyshop) is the same concept covered by these — no separate master needed. |
| 11 | **Service Package** (Periodic / Accident Repair / Combo) | ✅ | **FIXED 2026-06-03:** new `ServicePackageTypeMaster` (Periodic Service / Accident Repair / Combo Offer / AMC) + `service_package_type_id` FK (backfilled from `is_amc`) + Category select in the package form. |
| 12 | **Requested Repairs (Misc)** | ✅ | **FIXED 2026-06-03:** new `RequestedRepairMaster` + `job_card_requested_repair` pivot + a **Requested Repairs** multi-select on the Job Card (synced on save). |
| 13 | **Photo Type** | ✅ | **FIXED 2026-06-03:** `PhotoTypeMaster` (tabbed capture slots) now also seeds a **"Service Stage"** group with **BEFORE SERVICE / AFTER SERVICE**, matching the spec. |
| 14 | **Customer Approval Option** | ✅ | Master `CustomerApprovalTypeMaster` exists. **Decision:** consumed at the Estimate→Approval step (downstream transaction), not the intake Job Card form. |

---

## 3. Gap summary — all clear ✅

Every item from the spec is now either a configurable master, a wired field, or an intentional decision. No open gaps remain.

### Built / promoted to a configurable master (2026-06-03)
- **Customer Type** — `BusinessTypeMaster` relabelled "Customer Type"
- **GST Type** — `GstTypeMaster` linked to customers (`gst_type_id`) + form select
- **Fuel Type** — new `FuelTypeMaster` (variants use `fuel_type_id`)
- **Transmission Type** — new `TransmissionTypeMaster` (variants use `transmission_type_id`)
- **Registration Type** — new `RegistrationTypeMaster` (vehicles use `registration_type_id`)
- **Vehicle Type** — `VehicleSegmentMaster` relabelled "Vehicle Type" + LUXURY
- **Service Package category** — new `ServicePackageTypeMaster` + `service_package_type_id`
- **Requested Repairs** — new `RequestedRepairMaster` + pivot, wired into the Job Card
- **Photo Type** — added BEFORE / AFTER SERVICE stage seeds

### Intentional decisions (master exists; not on the intake Job Card by design)
- **Insurance Company** → used in Insurance / Bodyshop / claim transactions
- **Vendor / Vendor Type** → used in Outside-Work / procurement transactions
- **Customer Approval** → captured at the Estimate→Approval step
- **Department** → Workshop master on the Job Card; HR `DepartmentMaster` is a separate org-unit master
- **Job Type** → covered by `ComplaintTypeMaster` + `JobDescriptionMaster` (no separate master)

---

## 4. Verification

- ✅ marks are backed by **live-DB columns** (`Schema::getColumnListing`) and/or module presence — verified against the running database, not migrations alone.
- All new masters follow full convention (search/filter/sort, import/export, quick CRUD), are seeded, registered in `DatabaseSeeder`, and covered by tests. Full suite green after the changes.
- "Intentional decisions" reflect choices confirmed on 2026-06-03 to keep the intake Job Card lean and push Insurance/Vendor/Approval to their downstream transactions.

---

*Status: all spec items resolved as of 2026-06-03 — every row in §2 is ✅ (built, wired, or an intentional decision). The ⚠️/❌ symbols in the legend are no longer used by any finding.*
