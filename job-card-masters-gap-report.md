# Job Card — Masters & Submasters Gap Report

> **Purpose:** Compare the client's required Job Card masters/submasters (two sheets) against what currently exists in the Mquik system, and flag every gap.
> **Date:** 2026-06-03
> **Method:** Verified against actual database migrations and the `app/Modules/` directory — not assumptions. Items that could not be fully confirmed are flagged.

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
| 1b | — Customer Type | ✅ | Configurable master **`BusinessTypeMaster`** (seeded WALKING / LOYAL / CORPORATE / GOVERNMENT), linked via `customers.business_type_id`, fully wired into the customer form (combobox "Type" + inline quick-add), plus index filter & colour badge. The legacy `customer_type` enum **no longer exists** in the live DB. ⚠️ **Naming mismatch:** the master is currently labelled **"Business Types"** — consider renaming to **"Customer Type"** to match the spec. |
| 1c | — GST Type | ⚠️ | `GstTypeMaster` module **exists** but `customers` has **no `gst_type_id`** column — **not linked** |
| 2 | **Customer Vehicle** | ✅ | `CustomerVehicleMaster`, wired into Job Card |
| 2a | — Brand / Model / Variant / Segment / Colour | ✅ | `VehicleBrandMaster`, `VehicleModelMaster`, `VehicleVariantMaster`, `VehicleSegmentMaster`, `VehicleColorMaster` |
| 2b | — VIN | ✅ | Field `customer_vehicles.vin` (plus `registration_no`) |
| 2c | — Fuel Type | ⚠️ | Field `vehicle_models.fuel_type` enum (`petrol \| diesel \| cng \| electric \| hybrid`) — **not a master**, and lives on Model |
| 2d | — Transmission Type | ⚠️ | Field `vehicle_variants.transmission` enum — **not a master** |
| 2e | — Vehicle Type (Hatchback / Sedan / SUV / Luxury) | ❌ | No master, no field. Distinct from Segment — must not be conflated |
| 2f | — Registration Type | ❌ | `number_plate_type` was added then **dropped**; only the plate number `registration_no` remains |
| 3 | **Damage Type** (Scratch/Dent/Crack/Rust/Broken/Paint Fade) | ✅ | `DamageTypeMaster` — wired into the inventory tri-state |
| 4 | **Department** | ⚠️ | **Two** masters exist: `WorkshopDepartmentMaster` (used by Job Card) **and** `DepartmentMaster` (HR). Spec says only "Department" — ambiguous |
| 5 | **Service Type** | ✅ | `ServiceTypeMaster`, wired |
| 6 | **Insurance Company** | ⚠️ | `InsuranceCompanyMaster` exists but `job_cards` has **no insurance FK** — **not wired into Job Card** |
| 7 | **Employee** | ✅ | `EmployeeMaster`, wired (advisor / technician) |
| 8 | **Vendor + Vendor Type** | ⚠️ | `VendorMaster` + `VendorTypeMaster` exist but are **not referenced on the Job Card** |
| 9 | **Inventory Inside Vehicle** | ✅ | `VehicleInventoryItemMaster`, wired (tri-state present/missing/damaged) |
| 10 | **Complaint / Job Type / Job Description** | ⚠️ | `ComplaintTypeMaster` (wired) + `JobDescriptionMaster` (exists). No separate "Job Type" master; JobDescription wiring into the Job Card form **not confirmed** |
| 11 | **Service Package** (Periodic / Accident Repair / Combo) | ⚠️ | `ServicePackageMaster` wired, but only has an `is_amc` flag — **Periodic / Accident Repair / Combo categories are not modeled** |
| 12 | **Requested Repairs (Misc)** | ❌ | No master. Job Card only has free-text `suggested_services` / `notes` + complaints |
| 13 | **Photo Type** | ⚠️ | `PhotoTypeMaster` exists and is wired as tabbed capture slots, but seeded as **angle slots** (Front/Rear/Interior/Odometer…) — **does not reflect the spec's "Before Service / After Service" framing** |
| 14 | **Customer Approval Option** | ⚠️ | `CustomerApprovalTypeMaster` exists but is **not yet used in the Job Card form** |

---

## 3. Gap summary

### ❌ Genuinely missing (no master, no field)
1. **Vehicle Type** — Hatchback / Sedan / SUV / Luxury
2. **Registration Type** — was built as `number_plate_type`, then dropped
3. **Requested Repairs / Misc** master

### ⚠️ Master exists but **not wired into the Job Card**
- Insurance Company
- Vendor / Vendor Type
- Customer Approval Option
- Job Description (wiring unconfirmed)

### ⚠️ Captured as an inline field/enum where the spec implies a configurable master
- Fuel Type (enum on model)
- Transmission Type (enum on variant)
- GST Type (master exists but **not linked** to Customer)

### ⚠️ Naming mismatch (master exists, label differs from spec)
- **Customer Type** is implemented as the **`BusinessTypeMaster`** master (linked + wired). Consider renaming the master/label from "Business Types" to "Customer Type" to match the client's wording.

### ⚠️ Modeled but incomplete vs spec
- **Service Package** categories (Periodic / Accident Repair / Combo) — only an `is_amc` flag today
- **Photo Type** seed defaults — angle-based, missing the "Before / After Service" intent
- **Department** — two competing masters (Workshop vs HR); spec ambiguous

---

## 4. Verification caveats

- ✅ marks are backed by confirmed migration columns and/or module presence.
- ⚠️ "not wired into Job Card" is based on the absence of the relevant foreign key on `job_cards` and the relevant field in the Job Card form component — **runtime UI was not re-verified** for each.
- The two "Department" masters and the "Job Description" wiring are flagged as ambiguous and should be confirmed with the client before building.

---

*Report only — no code changes were made. Awaiting review before any build plan.*
