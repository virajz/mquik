# Job Card Creation — End-to-End Guide (5 Worked Scenarios)

> **Purpose:** A hands-on, click-by-click guide to creating a Job Card from scratch — including how to populate every master it depends on. Five unique scenarios progress from the simplest walk-in to a full new-vehicle + bodyshop + AMC flow, so by the end you've touched every master in the Job Card ecosystem.
>
> **Base URL:** `http://mquik.test` · **Date:** 2026-06-03 · Built against the live module set.

---

## 0. Before you start — read once

### 0.1 The golden rule of the Job Card form
The Job Card form **only selects existing records** for Customer and Vehicle (and all its master dropdowns). It does **not** create customers or vehicles inline. So the flow is always:

```
prepare masters  →  create Customer  →  create Customer Vehicle  →  create Job Card
```

If a dropdown on the Job Card is empty (no Department, no Service Type, no Photo Types…), you must add those master rows first.

### 0.2 Three ways to add master data

| Pattern | When you'll see it | How |
|---|---|---|
| **Master page** | Any master in the sidebar | Open the master (see Appendix A), click **+ New …**, fill, **Create**. |
| **Inline "Create …" option** | Single-field pickers (Customer Type, Address Region, Vehicle Colour, and Brand/Model inside the vehicle wizard) | Type the new value in the combobox → click the **Create "…"** row that appears. |
| **Quick-add wizard (＋ button)** | Multi-field targets (a new Vehicle = Brand→Model→Variant; a referring Customer) | Click the **＋** next to the picker → fill the mini-form in the modal → it's selected automatically. |

### 0.3 Conventions that apply everywhere
- **Capital typing:** every text field is stored UPPERCASE automatically — type naturally.
- **Phone:** 10 digits, `+91` is prefixed for you.
- **Registration No.:** accepts state series (`GJ05RH4816`) and BH series (`24BH1234AA`).
- **IDs:** every saved record shows as `#00042` / `JC-00042`.

### 0.4 The Job Card form, section by section (reference)
Every scenario below fills these sections, in this order:

1. **Customer & Vehicle** — pick customer, then its vehicle.
2. **Timing & Routing** — Opened date/time, Promised date/time, Department, Advisor, Technician, Service Type, Service Package, Status.
3. **Vehicle State at Receipt** — Odometer (km), Fuel Level (Empty / ¼ / ½ / ¾ / Full).
4. **Customer Complaints** — repeatable rows: Complaint text, Type, Severity (Low/Medium/High).
5. **Requested Repairs** — multi-select of misc requested jobs.
6. **Vehicle Inventory** — every item defaults **Present**; flag **Missing** or **Damaged** (Damaged → pick a Damage Type + note).
7. **Photos** — tabbed by photo group (Service Stage, Exterior, Interior, Meter, Engine, Wheels & Tyres, Documents); tap a slot to capture/retake.
8. **Additional / Damage Photos** — free multi-upload dropzone for extra/damage close-ups.
9. **Advisor Notes** — Suggested Services + Internal Notes.
10. **Terms & Acceptance** — T&C toggle + customer signature upload.

Then **Create Job Card** → you land on the Job Card list with a `JC-#####` toast.

---

## Scenario 1 — Walk-in periodic service (happy path, all masters pre-seeded)

**Profile:** Ramesh Patel walks in with his **Maruti Swift VXi (petrol)** for a routine 10,000 km service. Everything he needs already exists in the seeded masters.

**Goal:** Show the fastest end-to-end path with zero master creation beyond the customer + vehicle.

### Step 1 — Create the customer
1. Sidebar → **Customers → Customers** (`/customer-master`) → **+ New Customer**.
2. First Name `RAMESH`, Last Name `PATEL`.
3. **Customer Type** → `WALKING` (already in the list).
4. **GST Type** → leave blank (individual, optional).
5. Phone `9825012345`.
6. Address (optional): Label `HOME`, Address line, Region → search your city.
7. **Create**. → `Customer #00051 created.`

### Step 2 — Create the vehicle
1. Sidebar → **Customers → Customer Vehicles** (`/customer-vehicle-master`) → **+ New Customer Vehicle**.
2. **Customer** → search `RAMESH`.
3. **Vehicle** → search `Swift VXi` (Maruti Swift VXi is seeded). Pick it.
4. **Colour** → `SILVER` (or type a new one → "Create").
5. **Plate Type** → `PRIVATE`.
6. **Registration No.** → `GJ05RH4816`.
7. VIN / Engine No. / Odometer `9800` (optional).
8. **Create**. → `Vehicle #00071 created.`

### Step 3 — Create the Job Card
1. Sidebar → **Workshop → Job Cards** (`/job-cards`) → **+ New Job Card** (`/job-cards/create`).
2. **Customer & Vehicle:** Customer `RAMESH PATEL` → Vehicle `MARUTI SWIFT VXI — GJ05RH4816`.
3. **Timing & Routing:** Opened = today/now (pre-filled). Promised = tomorrow. Department `SERVICE`. Advisor → pick one. Technician → leave (auto-assign later). Service Type `PERIODIC MAINTENANCE`. Service Package → `PERIODIC SERVICE` package if defined. Status `Open`.
4. **Vehicle State:** Odometer `9800`, Fuel `½`.
5. **Complaints:** Add complaint → "ROUTINE 10K SERVICE, ENGINE OIL + FILTER", Type `GENERAL`, Severity `Low`.
6. **Requested Repairs:** (skip).
7. **Vehicle Inventory:** leave everything **Present**.
8. **Photos:** Meter tab → **Odometer** slot → capture. Exterior → Full Vehicle (optional).
9. **T&Cs:** toggle on; signature optional.
10. **Create Job Card** → `Job Card JC-00012 created.`

> **Takeaway:** When the masters are seeded, a job card is ~3 minutes: customer, vehicle, card.

---

## Scenario 2 — Corporate customer + brand-new vehicle (full master chain)

**Profile:** **Acme Logistics Pvt Ltd** (corporate, GST registered) brings in a **Citroën C3 (a brand/model not yet in the system)**. You must build the Brand → Model → Variant chain.

**Goal:** Exercise the **vehicle quick-add wizard** and inline master creation.

### Step 1 — Create the corporate customer
1. **Customers → Customers → + New Customer**.
2. Company name in First Name `ACME LOGISTICS PVT LTD` (leave last name blank).
3. **Customer Type** → `CORPORATE`.
4. **GST Type** → `REGULAR`.
5. Phone `9898981234`, Email `accounts@acme.example`.
6. **Create**.

### Step 2 — Prepare vehicle masters (inline, no leaving the form)
1. **Customer Vehicles → + New Customer Vehicle** → Customer `ACME LOGISTICS`.
2. **Vehicle** picker → click the **＋** (quick-add vehicle wizard):
   - **Brand** → type `CITROEN` → **Create "CITROEN"**.
   - **Model** → type `C3` → **Create "C3"**.
   - **Vehicle Type** → `HATCHBACK`.
   - **Variant name** → `C3 LIVE`.
   - **Fuel** → `PETROL` · **Transmission** → `MANUAL`.
   - **Save** → the new variant is selected.
   - *If a needed Fuel/Transmission value is missing, add it first at* `/fuel-type-master` *or* `/transmission-type-master` *(they're plain pickers, no inline create).*
3. Colour `WHITE`, Plate Type `COMMERCIAL`, Registration No `GJ01AB2024`.
4. **Create**.

### Step 3 — Create the Job Card
1. **Job Cards → + New Job Card**.
2. Customer `ACME LOGISTICS PVT LTD` → Vehicle `CITROEN C3 — GJ01AB2024`.
3. Timing & Routing: Department `SERVICE`, Advisor, Service Type `RUNNING REPAIR`, Status `Open`.
4. Vehicle State: Odometer `15200`, Fuel `¾`.
5. Complaints: "AC NOT COOLING", Type `AC ISSUE`, Severity `Medium`.
6. Photos: Service Stage → **Before Service**; Meter → **Odometer**.
7. T&Cs on. **Create Job Card** → `JC-00013`.

> **Takeaway:** A whole new vehicle lineage (Brand/Model/Variant) is built inside the vehicle form without ever leaving it. Fuel/Transmission/Registration types come from their masters.

---

## Scenario 3 — Accident / bodyshop job with damage mapping

**Profile:** **Hyundai Creta SUV** comes in after a front-end collision. Bodyshop department, accident-repair package, visible damage, photos to document condition. (Insurance details are captured later in the Insurance/claim transaction, not here.)

**Goal:** Exercise **Damage Type**, the **inventory Damaged state**, **photo damage documentation**, and the **Accident Repair** package category.

### Step 1 — Prepare masters (only if missing)
- **Workshop Department** `BODYSHOP` → if absent, `/workshop-department-master` → **+ New** → `BODYSHOP`.
- **Service Package** of category **Accident Repair** → `/service-package-master` → **+ New** → name `ACCIDENT REPAIR — STANDARD`, **Category** `ACCIDENT REPAIR`.
- **Damage Types** (`SCRATCH/DENT/CRACK/RUST/BROKEN/PAINT FADE`) are seeded; add `BUMPER CRACK` at `/damage-type-master` if you want a finer label.

### Step 2 — Customer + vehicle
- Reuse an existing customer, or create one (Scenario 1 steps). Vehicle: Hyundai **Creta SX** (seeded model) — create the Customer Vehicle with Plate Type `PRIVATE`, Reg `GJ05CR7777`.

### Step 3 — Job Card with damage
1. **Job Cards → + New Job Card** → pick customer + Creta.
2. **Timing & Routing:** Department `BODYSHOP`, Advisor, Service Type `ACCIDENT REPAIR` (add at `/service-type-master` if missing), Service Package `ACCIDENT REPAIR — STANDARD`, Status `Awaiting Approval`.
3. **Vehicle State:** Odometer `42100`, Fuel `¼`.
4. **Complaints:** "FRONT BUMPER & BONNET DAMAGED IN COLLISION", Type `BODY`, Severity `High`.
5. **Vehicle Inventory:**
   - Most items **Present**.
   - **MUSIC SYSTEM** → **Damaged** → Damage Type `CRACK` → note "SCREEN CRACKED".
   - **TOOL KIT** → **Missing** → note "NOT IN BOOT".
6. **Photos:**
   - **Service Stage → Before Service** (overall pre-repair shot).
   - **Exterior → Front, Front Left Corner, Front Right Corner** (the impact area).
7. **Additional / Damage Photos:** drop 3–4 **close-ups** of the bumper crack and bonnet dent.
8. **Advisor Notes:** Suggested Services "BUMPER REPLACEMENT, BONNET DENT REMOVAL + REPAINT".
9. T&Cs on; capture **customer signature**.
10. **Create Job Card** → `JC-00014` (status *Awaiting Approval*).

> **Takeaway:** Damage is documented two ways — structured (inventory **Damaged** + Damage Type) and visual (Before-Service slot + Additional/Damage close-ups).

---

## Scenario 4 — EV with missing accessories + customer-requested repairs

**Profile:** **Tata Nexon EV** (electric) in for a health check; the customer also asks for a few add-on jobs, and some accessories are missing.

**Goal:** Exercise the **Electric fuel type**, the **inventory Missing state**, and the **Requested Repairs** multi-select.

### Step 1 — Prepare masters
- Vehicle variant **Nexon EV** with **Fuel = ELECTRIC**, **Transmission = AUTOMATIC** — build via the vehicle wizard (Scenario 2). `ELECTRIC`/`AUTOMATIC` are seeded.
- **Requested Repairs** seeded with Wheel Alignment, AC Gas Refill, etc. To add one not present (e.g. `SOFTWARE UPDATE`), go to `/requested-repair-master` → **+ New** → `SOFTWARE UPDATE`.

### Step 2 — Customer + vehicle
- Customer `PRIYA SHARMA` (Walking). Vehicle: Tata **Nexon EV** variant, Colour `BLUE`, Plate `PRIVATE`, Reg `GJ27EV0001`.

### Step 3 — Job Card
1. **+ New Job Card** → pick customer + Nexon EV.
2. **Timing & Routing:** Department `SERVICE`, Advisor, Service Type `INSPECTION`, Status `Open`.
3. **Vehicle State:** Odometer `8800`. Fuel `Full` (battery — closest option).
4. **Complaints:** "RANGE DROPPED, REQUEST BATTERY HEALTH CHECK", Type `ELECTRICAL`, Severity `Medium`.
5. **Requested Repairs:** select **Wheel Alignment**, **AC Gas Refill**, **Software Update**.
6. **Vehicle Inventory:**
   - **SPARE TYRE** → **Missing** → note "EV HAS NO SPARE".
   - **TOOL KIT** → **Missing**.
   - **CHARGING CABLE** → if you maintain such an item, mark **Present**; else add it at `/vehicle-inventory-item-master`.
7. **Photos:** Meter → **Odometer**; Service Stage → **Before Service**.
8. **Create Job Card** → `JC-00015`.

> **Takeaway:** "Missing parts" are first-class — flag the inventory item **Missing** with a note; they're recorded even though they're absent. Requested Repairs capture customer add-ons separate from complaints.

---

## Scenario 5 — Commercial fleet vehicle under AMC (BH-series, composition GST)

**Profile:** **Tata Ace** light commercial vehicle, owned by a fleet on a **composition GST** scheme, serviced under an **Annual Maintenance Contract**, carrying a **BH-series** plate.

**Goal:** Exercise **Registration Type (BH Series / Commercial)**, **Vehicle Type COMMERCIAL**, **GST Type COMPOSITION**, and the **AMC** service-package category, plus an inline new Requested Repair.

### Step 1 — Prepare masters
- **GST Type** `COMPOSITION` (seeded).
- **Vehicle Type** `COMMERCIAL` (seeded under the relabelled Vehicle Types master).
- **Registration Type** `BH SERIES` (seeded). To add a finer one (e.g. `TAXI`), `/registration-type-master` → **+ New**.
- **Service Package** of category **AMC** → `/service-package-master` → **+ New** → `FLEET AMC — GOLD`, **Category** `AMC`, toggle **Is AMC Package**.
- **Requested Repair** `FITNESS CERTIFICATE RENEWAL` → add at `/requested-repair-master` if absent.

### Step 2 — Customer + vehicle
1. Customer `BHARAT FLEET SERVICES`, **Customer Type** `CORPORATE`, **GST Type** `COMPOSITION`.
2. Vehicle via wizard: Brand `TATA`, Model `ACE`, **Vehicle Type** `COMMERCIAL`, Variant `ACE GOLD`, Fuel `DIESEL`, Transmission `MANUAL`.
3. **Plate Type** `BH SERIES`, **Registration No.** `24BH1234AA` (BH format), Reg.
4. **Create**.

### Step 3 — Job Card
1. **+ New Job Card** → pick `BHARAT FLEET SERVICES` + the Tata Ace.
2. **Timing & Routing:** Department `SERVICE`, Advisor, Service Type `PERIODIC MAINTENANCE`, **Service Package** `FLEET AMC — GOLD`, Status `Open`.
3. **Vehicle State:** Odometer `64000`, Fuel `½`.
4. **Complaints:** "AMC SCHEDULED SERVICE — 60K", Type `GENERAL`, Severity `Low`.
5. **Requested Repairs:** **Fitness Certificate Renewal**, **Wheel Balancing**.
6. **Vehicle Inventory:** all **Present**; **STEPNEY** → note "WORN, ADVISE REPLACEMENT" (keep Present, add condition note).
7. **Photos:** Documents → **Number Plate**, **RC Book**; Meter → **Odometer**.
8. **Create Job Card** → `JC-00016`.

> **Takeaway:** Commercial/AMC jobs lean on the Registration Type, Vehicle Type, GST Type, and Service-Package-Category masters — all configurable, all reachable from the sidebar.

---

## Appendix A — Where each master lives

| Master | Sidebar group | URL |
|---|---|---|
| Customers | Customers | `/customer-master` |
| Customer Types (Business Types) | Customers | `/business-type-master` |
| GST Types | Finance | `/gst-type-master` |
| Customer Vehicles | Customers | `/customer-vehicle-master` |
| Vehicle Brands / Models / Variants | Vehicles | `/vehicle-brand-master`, `/vehicle-model-master`, `/vehicle-variant-master` |
| Vehicle Types (Segments) | Vehicles | `/vehicle-segment-master` |
| Fuel Types | Vehicles | `/fuel-type-master` |
| Transmission Types | Vehicles | `/transmission-type-master` |
| Registration Types | Vehicles | `/registration-type-master` |
| Vehicle Colours | Vehicles | (managed inline / colour master) |
| Workshop Departments | Workshop | `/workshop-department-master` |
| Service Types | Workshop | `/service-type-master` |
| Service Packages | Workshop | `/service-package-master` |
| Service Package Types | Workshop | `/service-package-type-master` |
| Requested Repairs | Workshop | `/requested-repair-master` |
| Complaint Types | CRM | `/complaint-type-master` |
| Damage Types | Workshop | `/damage-type-master` |
| Photo Types (capture slots/tabs) | Workshop | `/photo-type-master` |
| Vehicle Inventory Items | Workshop | `/vehicle-inventory-item-master` |
| Employees (Advisors/Technicians) | HR | `/employee-master` |
| Job Cards | Workshop | `/job-cards` |

## Appendix B — Master coverage across the 5 scenarios

| Master / feature | S1 | S2 | S3 | S4 | S5 |
|---|:--:|:--:|:--:|:--:|:--:|
| Customer Type | Walking | Corporate | — | Walking | Corporate |
| GST Type | — | Regular | — | — | Composition |
| New Brand/Model/Variant (wizard) | — | ✅ | — | ✅ | ✅ |
| Fuel Type | Petrol | Petrol | — | **Electric** | Diesel |
| Registration Type | Private | Commercial | Private | Private | **BH Series** |
| Vehicle Type | Hatchback | Hatchback | SUV | SUV | **Commercial** |
| Service Package category | Periodic | — | **Accident Repair** | — | **AMC** |
| Complaints | ✅ | ✅ | ✅ (High) | ✅ | ✅ |
| Requested Repairs | — | — | — | ✅ | ✅ (+inline new) |
| Inventory: Missing | — | — | ✅ | ✅ | — |
| Inventory: Damaged + Damage Type | — | — | ✅ | — | — |
| Photos: Before/After Service | — | ✅ | ✅ | ✅ | — |
| Photos: Damage close-ups | — | — | ✅ | — | — |
| Photos: Documents | — | — | — | — | ✅ |
| Signature captured | — | — | ✅ | — | — |

---

*By completing all five scenarios you will have created (or exercised) every master the Job Card depends on, and produced five Job Cards (`JC-00012` … `JC-00016`) covering walk-in service, new-vehicle onboarding, bodyshop/accident, EV with missing parts, and commercial AMC.*
