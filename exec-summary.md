# Mquik ERP — Internal Execution Summary

> **Audience:** Me. Not the client. Not the world.
> **Purpose:** The real strategy that makes 127 modules in 8 weeks possible solo.
> Keep this private. The client-facing deck talks about outcomes, not mechanics.

---

## The Deal

- **127 modules** across 21 layers (see `modules.md`)
- **8 weeks** of build time
- **1 developer** (me), Claude as pair-programmer
- **All 3 phases** in scope

This is only possible because of three strategic bets. Miss any one, the timeline collapses.

---

## Bet 1 — Force-multiply with code generation

**Day 1, Week 1: build `php artisan make:module {Name}`** that scaffolds:

- Migration · Model · Factory · Seeder
- Livewire index/create/edit components extending `ResourceComponent`
- Flux UI form rendered from a YAML field schema (no hand-written Blade per module)
- Pest test stubs (CRUD + permission + audit)
- Permission entries in `Authorization`
- Menu entry (auto-hidden by permission)
- Audit log subscriber
- Master search indexer

After this exists, a typical CRUD module costs **20-40 minutes**, not 4 hours.

This is the most important code in the project. If Day 1 ends without it working, the entire plan slips.

---

## Bet 2 — One engine per pattern

Build these once, register everything else against them:

| Engine | Replaces | Saves |
|---|---|---|
| `ApprovalEngine` | Every `*Approval` module + request/response flows | ~10 hand-written modules |
| `NotificationEngine` | All WhatsApp/SMS/Email + template wiring | ~20 hours of glue code |
| `ReportEngine` | Filter UI + export + drag-drop columns generic | ~50 hours across 98 reports |
| `SearchEngine` | Per-module search screens | ~15 hours |
| `FollowUpEngine` | 5 follow-up modules (service due, recommended, sales inquiry, insurance, AMC) | 4 modules collapsed to 1 |

The 127-module count effectively becomes **~40 hand-written modules + 87 config registrations**.

---

## Bet 3 — Ship · Function · Stub (be honest per module)

| Tier | Definition | Count | Examples |
|---|---|---|---|
| **SHIP** | Polished, tested, UAT-ready, edge cases handled | ~35 | JobCard · Proforma · Invoices · IPO · Inventory · Receipt · GatePass · Dashboard |
| **FUNCTION** | Works end-to-end, happy-path tested, plain Flux UI | ~70 | Most masters, secondary flows, OWI/OWR, Surveyor, accounting modules, follow-ups, mobile API surface |
| **STUB** | Interface + UI shell + dummy adapter, real impl post-launch | ~22 | ANPR live · Tecdoc · Biometric · AI · GST portal sync · Razorpay live · native apps · multi-branch |

The "Coming soon" badge on a stubbed feature is a feature, not a failure. Don't lie in UAT.

---

## Daily Working Pattern (10h × 6 days)

| Block | I drive | Claude assists |
|---|---|---|
| 30 min | Plan today's modules · schema · edge cases | Reviews plan · flags concerns |
| 6 h | Keyboard · UX/business calls · architecture | Generates components/tests/migrations from spec · pairs on debugging |
| 1 h | Walk every screen · file issues | Runs Pest · fixes failing tests |
| 1.5 h | Demo to self (record video) · deploy | Drafts release notes · seed data |
| 1 h | Read tomorrow's requirement · sketch wireframes | Drafts schema · pre-loads next-day context |

**Don't:** ask AI to design business logic from a vague prompt.
**Do:** decide schema and behaviour myself, then ask AI to implement precisely.

Use `boost` MCP relentlessly: `database-schema`, `database-query`, `read-log-entries`, `last-error`, `browser-logs`, `search-docs`. Never read docs by hand.

---

## Daily Discipline (the only way this works)

1. **No bug-chasing rabbit holes.** Not blocking the next demo? File and move on. Triage Saturday morning.
2. **One PR per module per day.** No multi-day branches.
3. **Pest green before sleep.** No exceptions.
4. **Friday demo to self (record video).** Watch Saturday morning with fresh eyes — I'm the QA.
5. **Sundays off.** Burnout in Wk 4 = no system in Wk 8.
6. **Plan tomorrow before bed.** AI works best with a plan.

---

## Module Tier Map

### SHIP (~35) — full polish, all 9 DoD items

Tenancy · Auth · Authorization · AuditLog · 11 Masters · JobCard · DigitalInspection · IPO · VPO · Purchase · SalesEstimate · EstimateApproval · Proforma · RegularSalesInvoice · CounterSalesInvoice · SalesReturn · Receipt · Payment · GatePass · VehicleOutward · Dashboard · Reporting · MasterSearch

### FUNCTION (~70) — works, tested, plain UI

All other masters · Attendance · LeaveManagement · Payroll · Appointment · PickupDrop · GateInOut · JobHistory · JobCancel · IPI · IPR · VPI · VPR · PartsReceiving · PartsHandover · Challan · PurchaseReturn · Consumable · GoodsReturnNote · OWI · OWR · OWO · OutsideLabourBilling · ClaimIntimation · SurveyorInspection · DocumentCollection · DocumentDelivery · DeliveryOrder · InsurancePolicy · CustomerWarrantyClaim · VendorWarrantyClaim · ServicePackage · EInvoice · ExcessStockApproval · all Advance flows · ChartOfAccounts · Ledger · BankCashBook · Expense · AssetManagement · CreditLimit · TaxCompliance · GstFiling · FinancialStatements · FollowUp · SalesInquiry · ServiceReferral · CustomerComplaint · CustomerFeedback · AdvisorFeedback · ComplaintRegister · PublicApi · CustomerApp · EmployeeApp · all Gateways · Scheduler · AnalyticsKpi · MisReport · SmartSalary · TargetIncentive · ProformaApproval · ProformaCosting · InvoiceCorrection · VPOApproval · LateMemo · OutstandingTracker · PurchaseQuoteRequest · MenuPreferences · Backup · KeyboardShortcuts · PrintAll · StockCounting · Inventory · StockMovement · Barcode · FinalWorkOrder · FinalWorkResponse · FinalInspection

### STUB (~22) — interface + shell, real impl post-launch

ANPR camera live integration (manual fallback works) · Tecdoc spare lookup · Biometric login · AI Assistant (only 1-2 helpers actually call Claude) · GST portal live sync (data ready, no real submit) · Razorpay live mode (sandbox only) · WhatsApp Business API official approval (use SMS until cleared) · Native iOS app · Native Android app · Advanced BI dashboards · Surveyor RI sub-flow polish · Multi-branch tenancy (single workshop only) · Tax Audit Report · Custom inspection template designer · Reverse warranty deep search · Cross-sale/Up-sale ML suggestions · Painting analysis advanced · Repeat job ML detection · Vendor analysis ML scoring · 3-time reminder fine-tuning · Camera bay-wise customer share · Combo Service ML upsell

---

## Things to Cut Mercilessly (push-back script)

| Ask | Push-back |
|---|---|
| "Can the AI also write the test cases for me?" | Yes — but I write assertions for any business calculation. AI can't catch math errors. |
| "Let's also do the iOS app in Wk 8" | No. Native apps = post-launch. Web is responsive — that's the deal. |
| "Can we add a custom report for X?" | Reports = 30 min via ReportEngine after Wk 7. Ask post-launch. |
| "Real-time WhatsApp two-way chat" | Out of scope. SMS/WhatsApp link approval flow only. |
| "Multi-branch / multi-tenant" | Single workshop only in this 2 months. |
| "AI-suggested cross-sell on every screen" | 1-2 AI helpers ship Wk 8. Rest = Phase 4. |

---

## What Can Kill This Plan

| Killer | Mitigation |
|---|---|
| Master data not ready Wk 1 | Demand spares/labour Excel from client by Day 1. If absent, Wk 1 ends with seeders for fake data and slips. |
| Designing as I build | 2 hours every Sunday sketching next week's schemas in pen on paper. |
| Switching tools/libraries mid-build | Lock the stack on Day 1. No new packages without 30-min boost-docs check. |
| WhatsApp/SMS provider account approval | Sign up Day 1. SMS as fallback if WA Business not approved by Wk 7. |
| Scope creep ("just one more thing") | Print SHIP/FUNCTION/STUB table. Tape to the monitor. |
| Burnout | Sundays off. 6-day weeks. No 14-hour days after Wk 3. |
| Single point of failure (real-life event) | Have a backup plan. At least one trusted dev briefed on the architecture. |

---

## Honest Probability Assessment

| Outcome | Probability | What it looks like |
|---|---|---|
| All 127 modules present, tiered as planned | **55%** | Plan as written. Client trains on live system end of Wk 8. |
| ~100 modules ship, 27 slip to weeks 9-10 | **30%** | Wk 5 density bites. Bodyshop + Insurance + OWI slip 2 weeks. |
| ~70 modules in 8 weeks (major slip) | **12%** | Module generator (Wk 1) didn't materialise OR burnout hit. Plan a 12-week reality. |
| Catastrophic | **3%** | Single point of failure (me) had a real-life event. |

If 55% materialises: I've shipped a complete workshop ERP solo in 8 weeks. That is genuinely exceptional, and only AI-assisted development makes it possible.

---

## Foundation Investments — Week 1 Day-by-Day (the make-or-break week)

| Day | Output |
|---|---|
| 1 | Project shell · brand theme · Flux app shell · role-aware menu engine · `make:module` generator |
| 2 | Core · Tenancy · Auth (Fortify) · Authorization · AuditLog |
| 3 | ApprovalEngine · NotificationEngine · SearchEngine |
| 4 | ResourceIndex · ResourceForm · WorkflowStatusTree · Barcode service |
| 5 | ReportEngine · 5 simplest masters via generator |
| 6 | Employee/Customer/Vehicle/Spare/Labour/InspectionTemplate masters · Dashboard shell · MenuPreferences |

End of Wk 1: 11 masters live · 4 engines live · CRUD generator working · login → role-aware menu → CRUD anything → audit log → search → reports work for what exists.

---

## Mantras

- **Ship daily.** A live ugly screen beats a perfect unfinished one.
- **Honesty over optics.** Stubs marked "Coming soon" build trust. Lies in UAT destroy it.
- **Schema decisions are mine.** Implementation is shared with Claude.
- **The generator is the project.** Without it, the plan dies in Week 3.
- **Sundays off.** Always.

---

## Files

- `requirements.md` — original client requirements (don't edit)
- `modules.md` — 127-module architecture map
- `timeline.md` — public 8-week timeline (still mostly safe to share — light on the "solo" framing)
- `exec-summary.md` — **this file, internal only**
- `public/presentation.html` — client-facing non-technical deck
