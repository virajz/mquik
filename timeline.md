# Mquik ERP — Solo + AI · 2-Month Timeline (All Phases)

**Window:** 8 weeks · 1 developer · Claude as pair-programmer
**Goal:** All 127 modules from `modules.md` shipped with working UI in 8 weeks.
**Stack:** Laravel 13 · Livewire 4 · Flux UI Pro v2 · Tailwind v4 · Pest 4 · Fortify v1 · Laravel Cloud.

---

## Reality Check (read this first)

127 polished modules in 8 weeks solo is **not** achievable by writing them one at a time. It **is** achievable if you commit to three strategic bets:

### Bet 1 — Force-multiply with code generation
Build a `php artisan make:module` command in **Week 1, Day 1** that scaffolds:
- Migration · Model · Factory · Seeder
- Livewire index/create/edit components extending a base `ResourceComponent`
- Flux UI form rendered from a YAML/PHP schema (no hand-written Blade per module)
- Pest test stubs (CRUD + permission + audit)
- Permissions registered in `Authorization`
- Menu entry registered (auto-hidden by permission)
- Audit log subscriber wired
- Master search indexer registered

After this exists, a typical CRUD module costs **20-40 minutes**, not 4 hours.

### Bet 2 — One engine per pattern, used by many modules
Build these once, register everything else against them:
- **`ApprovalEngine`** — handles every `*Approval` and request/response flow. New approval = a config entry, not a module.
- **`NotificationEngine`** — template registry + WhatsApp/Email/SMS adapters. New notification = a row in `notification_templates`.
- **`ReportEngine`** — registers report definitions (filters + columns + query). New report = a class implementing `ReportDefinition`. Filter UI, export, drag-drop column ordering all generic.
- **`SearchEngine`** — modules implement `Searchable`; one global search UI handles all of them.
- **`FollowUpEngine`** — service due, recommended service, sales inquiry, insurance renewal, AMC due — one engine, polymorphic targets.

The 127-module count drops to roughly **40 hand-written modules + 87 config registrations** when you build it this way.

### Bet 3 — Ship · Function · Stub (be honest per module)

| Tier | Definition | Count | Examples |
|---|---|---|---|
| **SHIP** | Fully polished, tested, UAT-ready, edge cases handled | ~35 | JobCard, Proforma, Invoices, IPO, Inventory, Receipt, GatePass, Dashboard |
| **FUNCTION** | Works end-to-end, tests cover happy path, UI is plain Flux | ~70 | Most masters, follow-ups, secondary workflows, OWI/OWR, Surveyor |
| **STUB** | Interface + UI shell + dummy adapter, real integration post-launch | ~22 | ANPR camera, Tecdoc, Biometric, AI Assistant, full GST portal sync, mobile-native apps (web responsive instead), Razorpay live (sandbox only) |

Owning this distinction up front is what makes 8 weeks possible.

---

## How AI fits into your day (working pattern)

**Daily rhythm — ~10 hour day, 6 days/week:**

| Block | What you do | What Claude does |
|---|---|---|
| 30 min plan | Write today's module list, schema decisions, edge cases | Reviews the plan, flags missing concerns |
| 6h build | Drive the keyboard, make schema/UX/business decisions | Generates components, tests, migrations from your spec; pairs on debugging |
| 1h verify | Run tests, walk the UI, file issues | Runs Pest suite, fixes failing tests, refactors |
| 1.5h ship | Demo, deploy to staging, write release notes | Drafts release notes, generates seed data |
| 1h study | Read tomorrow's requirement section, sketch wireframes | Pre-loads context, drafts schema |

Use `boost` MCP relentlessly: `database-schema`, `database-query`, `read-log-entries`, `last-error`, `browser-logs`, `search-docs`. Never read docs by hand.

**Don't:** ask AI to design business logic from a vague prompt. **Do:** decide the schema and behaviour yourself, then ask AI to implement it precisely.

---

## Foundation Investments (Week 1 — make or break)

Everything in Week 1 is a force multiplier. If Week 1 slips, Phase 3 dies.

### Day 1 — Project shell + module generator
- Repo, Pint, Pest, CI to Laravel Cloud
- `nwidart/laravel-modules` (or thin custom convention)
- Brand theme tokens (orange `#EE7C2B`, grey `#8A8A8A`) in Tailwind v4
- App shell — Flux sidebar/topbar, role-aware menu engine, breadcrumbs
- **`php artisan make:module {Name}`** generator (the most important code in this project)

### Day 2 — Core / Tenancy / Auth / Authorization
- `Core` — base classes, contracts, traits, value objects (Money, GSTIN, RegNo, HSN)
- `Tenancy` — workshop entity, GST, T&Cs, working-hours config
- `Auth` (Fortify) — login, idle logout, single session enforcement
- `Authorization` — permission matrix, role↔designation mapping
- `AuditLog` — global model observer

### Day 3 — Engines
- `ApprovalEngine` (DB-backed approval requests + Flux UI for review/approve)
- `NotificationEngine` (templates table + 3 channel adapters, all stubbed at first)
- `SearchEngine` (Searchable contract + one global search Livewire screen)

### Day 4 — UI primitives
- Generic `ResourceIndex` Livewire (table, filters, pagination via flux:pagination, export)
- Generic `ResourceForm` Livewire (renders Flux form from YAML field spec, capital-typing trait, auto-ID display, entry datetime)
- Generic `WorkflowStatusTree` component
- `Barcode` service (gen + label print)

### Day 5 — Reporting + masters wave 1
- `ReportEngine` (definition registry, filter UI, Excel + PDF export, drag-drop columns)
- 5 simplest masters via the generator: `RegionMaster`, `InventoryGroupMaster`, `ServiceTypeMaster`, `InsuranceCompanyMaster`, `VendorMaster`

### Day 6 — Masters wave 2 + dashboard shell
- `EmployeeMaster`, `CustomerMaster`, `VehicleMaster`, `SpareMaster` (with Excel bulk import + tyre fields), `LabourMaster`, `InspectionTemplateMaster`
- `Dashboard` shell with empty role-aware widget slots
- `MenuPreferences` (drag-drop pin)

**End of Week 1:** 11 masters live · CRUD generator working · 4 engines live · login → role-aware menu → CRUD anything → audit log → search → reports work for what exists.

---

## Week 2 — HR + Front-Desk + Job Card Core

| Day | Modules | Notes |
|---|---|---|
| 1 | `Attendance`, `LeaveManagement`, `LateMemo` | Web punch (selfie via webcam) |
| 2 | `Payroll` (manual entry path), `SmartSalary` skeleton (KPI registry, formulas in Wk 7) | Salary slip print |
| 3 | `Appointment`, `PickupDrop`, `GateInOut` (manual + image; ANPR adapter stubbed) | Reg-No standardiser `GJ 05 AA 1234` |
| 4 | `JobCard` (T&C, complaints, photos, vehicle inventory snapshot, signature) | Hand-write — too central for the generator |
| 5 | `DigitalInspection` (PMS/Tyre/Bodyshop/Basic/Custom from `InspectionTemplateMaster`) | Image capture, IA/FA/OK outcomes |
| 6 | `JobHistory` read-model + status tree projection · `JobCancel` via ApprovalEngine | Read-model rebuilds from events |

**End of Week 2:** Vehicle gate-in → JC → digital inspection works end-to-end. Attendance + payroll skeleton live.

---

## Week 3 — Parts Procurement Chain (the longest workflow in the system)

| Day | Modules | Notes |
|---|---|---|
| 1 | `Inventory` (FIFO, MIN/MAX, qty filters) · `Barcode` integration | Read-model from purchase events |
| 2 | `InternalPartsInquiry` (IPI) → `InternalPartsResponse` (IPR) | Both via generator + custom workflow piece |
| 3 | `VendorPartsInquiry` (VPI) → `VendorPartsResponse` (VPR) — vendor comparison view | Multi-vendor compare grid |
| 4 | `InternalPartOrder` (IPO) — dynamic item status, carry-forward, cancel via ApprovalEngine | Complex — hand-write |
| 5 | `VendorPurchaseOrder` (VPO) + `VPOApproval` + `PurchaseQuoteRequest` | ₹25k threshold via ApprovalEngine |
| 6 | `PartsReceiving` (GRN + QC), `PartsHandover`, `Challan` (barcode/QR scan) | Challan via generator |

**End of Week 3:** Advisor requests brake pad → not in stock → vendor inquiry → PO with admin approval → goods received → handed to technician.

---

## Week 4 — Purchase, Estimate, Proforma, Invoicing

| Day | Modules | Notes |
|---|---|---|
| 1 | `Purchase` (Tax Invoice/BoS, add-from-Challan, item-wise datetime, warranty), `PurchaseReturn` | Hand-write |
| 2 | `Consumable` (add-from-Purchase, ML→GM, job-no consumption) · `GoodsReturnNote` | |
| 3 | `SalesEstimate` (Reg + Ins, group/item-wise, template add, ins-share computation) | Hand-write — central |
| 4 | `EstimateApproval` (WhatsApp/email link + customer digital approval + self-approval) | Uses ApprovalEngine + NotificationEngine |
| 5 | `Proforma` + `ProformaApproval` + `ProformaCosting` (auth-only P&L view) | Most complex screen in the system |
| 6 | `RegularSalesInvoice`, `CounterSalesInvoice`, `SalesReturn`, `InvoiceCorrection` | Reuse Proforma form with mode switch |

**End of Week 4:** Approved estimate → proforma → admin approves → invoice (Reg/Ins/Counter) → return → correction. Half the system is live.

---

## Week 5 — Cash, Closure, Outside Work, Bodyshop & Insurance

| Day | Modules | Notes |
|---|---|---|
| 1 | `Receipt` (merged Reg+Counter, multi-invoice settlement, redirect-to-Gatepass), `ReceiptRefund`, `Payment`, `PaymentRefund`, `OutstandingTracker` | OutstandingTracker = read-model |
| 2 | `FinalWorkOrder`, `FinalWorkResponse`, `FinalInspection`, `GatePass`, `GatePassApproval`, `VehicleOutward` | GatePass = two types (Work Pending / Final Delivery) |
| 3 | `OutsideWorkInquiry`, `OutsideWorkResponse`, `OutsideWorkOrder`, `OutsideLabourBilling` | Mirrors internal flow — heavy AI generation from the IPI/IPO pattern |
| 4 | `ClaimIntimation`, `SurveyorInspection`, `DocumentCollection`, `DocumentDelivery`, `DeliveryOrder` | Bodyshop chain |
| 5 | `InsurancePolicy`, `CustomerWarrantyClaim`, `VendorWarrantyClaim`, `ServicePackage` | Warranty + AMC packages |
| 6 | `EInvoice` (IRN/QR generation; portal sync stubbed), `ExcessStockApproval`, `AdvanceReceiptRequest`, `AdvanceReceiptEntry`, `AdvancePaymentRequest`, `AdvancePaymentEntry` | Stub portal · real sync post-launch |

**End of Week 5:** Full lifecycle works. Bodyshop and insurance flows present. Outstanding warning: Wk 5 is the densest week — buffer none, prepare to slip Day 6 items into Wk 6 if needed.

---

## Week 6 — Accounting, CRM Follow-ups, Notifications, Mobile API

| Day | Modules | Notes |
|---|---|---|
| 1 | `ChartOfAccounts`, `Ledger` (every transactional module starts posting via Ledger), `BankCashBook` | Wire existing modules via event listeners |
| 2 | `Expense`, `AssetManagement`, `CreditLimit`, `TaxCompliance` | Generator-friendly |
| 3 | `GstFiling` (Input/Output, GSTR-1, GSTR-2B, GSTR-3B, GSTR-9 — data prep + Excel export; portal sync stubbed) · `FinancialStatements` (Trial Balance, P&L, Balance Sheet, Cash Flow) | Reports via ReportEngine |
| 4 | `FollowUpEngine` (one polymorphic engine drives Service Due, Recommended, Sales Inquiry, Insurance Renewal, AMC Due) · `SalesInquiry` · `ServiceReferral` | Single engine, 5 follow-up types as config |
| 5 | `CustomerComplaint`, `CustomerFeedback`, `AdvisorFeedback`, `ComplaintRegister` (internal) | Generator-friendly |
| 6 | `PublicApi` (versioned API resources) · `CustomerApp` API surface · `EmployeeApp` API surface — backed by responsive web UI; native apps deferred | Mobile = responsive web in this 2-month plan |

**End of Week 6:** Accounting books balance. Follow-ups send automatically. CRM loop closes. API surface ready for future native apps.

---

## Week 7 — Notifications Live, Reports Wave, Smart Salary, Integrations

| Day | Modules | Notes |
|---|---|---|
| 1 | `WhatsAppGateway`, `EmailGateway`, `SmsGateway` go from stub to live; all `NotificationTemplate` entries wired to events | Pick provider with the fastest signup (likely MSG91 or similar) |
| 2 | `Scheduler` cron registry — service-due 3-time reminders, daily MIS auto-email, alignment-due, insurance-renewal nudges | Use Laravel scheduler |
| 3 | `Reporting` wave — register all 98 report definitions against the engine | Bulk AI-generation from the requirements table |
| 4 | `Dashboard` widgets per role + `AnalyticsKpi` aggregations + `MisReport` daily auto-email | Charts via livewire-charts or chart.js |
| 5 | `MasterSearch` v2 — register every transactional module as Searchable; reverse warranty tracking | Polish the global search |
| 6 | `SmartSalary` formulas (KPI/KRI engine — points, deductions, billing ratio 70/30, repeat-job, TAT, attendance) wired into `Payroll` · `TargetIncentive` engine | Most complex math in the system |

**End of Week 7:** All notifications fire. All reports defined. Dashboards show real data. Smart Salary calculates incentives.

---

## Week 8 — Stubs, Polish, UAT, Go-Live

| Day | Modules / Work | Notes |
|---|---|---|
| 1 | `PaymentGateway` (Razorpay sandbox live), `CameraIntegration` (ANPR adapter stub + manual capture polished), `TecdocIntegration` (interface + flag for post-launch), `BiometricIntegration` (interface stub), `AiAssistant` (interface + 1-2 helpers via Claude API) | Stubs honestly stubbed |
| 2 | `StockCounting` (quarterly physical reconciliation, mismatch report) · `Backup` (daily auto + restore confirmation) · `KeyboardShortcuts` config screen · `PrintAll` bulk printing | |
| 3 | Bug bash — walk every screen as every role, fix what hurts | Keep a triage list — don't chase polish on stubbed features |
| 4 | UAT Day 1 with client — golden path, master data import, real workflow walkthrough | Have client sit with you |
| 5 | UAT Day 2 — bug fixes from yesterday, secondary workflows, reports | |
| 6 | Go-live — DNS cutover, production seed, training session, hand-over runbook | First production support day |

**End of Week 8:** System is **live** with all 127 modules present — most polished, some functional, a known set stubbed with honest interfaces.

---

## Module Tier Map (what gets which treatment)

### SHIP (35 modules — full polish, all 9 DoD items)
Tenancy · Auth · Authorization · AuditLog · 11 Masters · JobCard · DigitalInspection · IPO · VPO · Purchase · SalesEstimate · EstimateApproval · Proforma · RegularSalesInvoice · CounterSalesInvoice · SalesReturn · Receipt · Payment · GatePass · VehicleOutward · Dashboard · Reporting · MasterSearch

### FUNCTION (70 modules — works, tested, plain UI)
All other masters · Attendance · LeaveManagement · Payroll · Appointment · PickupDrop · GateInOut · JobHistory · JobCancel · IPI · IPR · VPI · VPR · PartsReceiving · PartsHandover · Challan · PurchaseReturn · Consumable · GoodsReturnNote · OWI · OWR · OWO · OutsideLabourBilling · ClaimIntimation · SurveyorInspection · DocumentCollection · DocumentDelivery · DeliveryOrder · InsurancePolicy · CustomerWarrantyClaim · VendorWarrantyClaim · ServicePackage · EInvoice · ExcessStockApproval · all Advance flows · ChartOfAccounts · Ledger · BankCashBook · Expense · AssetManagement · CreditLimit · TaxCompliance · GstFiling · FinancialStatements · FollowUp · SalesInquiry · ServiceReferral · CustomerComplaint · CustomerFeedback · AdvisorFeedback · ComplaintRegister · PublicApi · CustomerApp · EmployeeApp · all Gateways · Scheduler · AnalyticsKpi · MisReport · SmartSalary · TargetIncentive · ProformaApproval · ProformaCosting · InvoiceCorrection · VPOApproval · LateMemo · OutstandingTracker · PurchaseQuoteRequest · MenuPreferences · Backup · KeyboardShortcuts · PrintAll · StockCounting · Inventory · StockMovement · Barcode · FinalWorkOrder · FinalWorkResponse · FinalInspection

### STUB (22 modules — interface + shell, real impl post-launch)
ANPR camera live integration (manual fallback works) · Tecdoc spare lookup · Biometric login · AI Assistant (only 1-2 helpers actually call Claude) · GST portal live sync (data ready, no real submit) · Razorpay live mode (sandbox only) · WhatsApp Business API official approval (use SMS until cleared) · Native iOS app · Native Android app · Advanced BI dashboards · Surveyor RI sub-flow polish · Multi-branch tenancy (single workshop only) · Tax Audit Report · Custom inspection template designer · Reverse warranty deep search · Cross-sale/Up-sale ML suggestions · Painting analysis advanced · Repeat job ML detection · Vendor analysis ML scoring · 3-time reminder fine-tuning · Camera bay-wise customer share · Combo Service ML upsell

These 22 are honestly marked in the UI ("Coming soon" or "Manual mode"). Don't lie about them in UAT.

---

## Things to Cut Mercilessly (and how to push back when asked)

| Ask | Push-back |
|---|---|
| "Can the AI also write the test cases for me?" | Yes, but you must write the assertions manually for any business calculation — AI tests can't catch math errors |
| "Let's also do the iOS app in week 8" | No. Native apps = post-launch. Web is responsive — that's the deal. |
| "Can we add a custom report for X?" | Reports are 30 min via ReportEngine after Week 7 — ask post-launch |
| "Real-time WhatsApp two-way chat" | Out of scope. SMS link approval flow only. |
| "Multi-branch / multi-tenant" | Single workshop only in this 2 months |

---

## Daily Discipline (the only way this works)

1. **No bug-chasing rabbit holes.** If a bug isn't blocking the next demo, file it and move on.
2. **One PR per module, every day.** No multi-day branches.
3. **Pest must be green before you sleep.** No exceptions.
4. **Friday demo to yourself (record video).** You're the QA. Watch yesterday's video on Saturday morning.
5. **Sunday off.** Burnout in week 4 = no system in week 8.
6. **Write tomorrow's task list before bed.** AI works best when you arrive with a plan.

---

## What can kill this plan

| Killer | Mitigation |
|---|---|
| Master data not ready Week 1 | Demand spares/labour Excel from client by Day 1. If absent, Wk 1 ends with seeders for fake data and slips. |
| Trying to design as you build | Spend 2 hours every Sunday sketching next week's schemas in pen on paper |
| Switching tools/libraries mid-build | Lock the stack on Day 1. No new packages without 30-min boost-docs check. |
| WhatsApp/SMS provider account approval | Sign up on Day 1. Use SMS as fallback if WA not approved by Wk 7. |
| Scope creep from "just one more thing" | Print the SHIP/FUNCTION/STUB table. Tape it to the monitor. |
| Burnout | Sundays off. 6-day weeks. No 14-hour days after Week 3. |

---

## Honest Probability Assessment

| Outcome | Probability | What it looks like |
|---|---|---|
| All 127 modules present, 35 polished, 70 functional, 22 honest stubs | **55%** | The plan as written. Client trains on live system end of Wk 8. |
| ~100 modules ship, 27 slip to weeks 9-10 | **30%** | Wk 5 density bites. Bodyshop + Insurance + Outside Work slip 2 weeks. |
| Major slip — only ~70 modules in 8 weeks | **12%** | Module generator (Wk 1) didn't materialise · or burnout hit. Plan a 12-week reality. |
| Catastrophic — production not stable in 8 weeks | **3%** | Single point of failure (you) had a real-life event. Have a backup plan: who do you call? |

If the 55% outcome materialises: you've shipped a complete workshop ERP solo in 8 weeks. That is genuinely exceptional, and only AI-assisted development makes it possible.
