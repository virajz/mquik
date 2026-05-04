# Mquik ERP — Laravel Modules

Each module is a self-contained bounded context with a single clear responsibility. Modules communicate via events and a shared `Core` contract layer (no direct cross-module model use). Designed for `nwidart/laravel-modules` (or similar) layout under `Modules/`.

Granularity rule applied: a module owns **one workflow / one aggregate root family**. When a feature spans workflows (e.g., "warranty"), it's split between the originating module and a dedicated coordinator.

---

## 1. Foundation Layer

### 1.1 `Core`
Shared kernel — base classes, contracts, enums, traits, value objects (Money, GSTIN, RegNo, HSN, Barcode). No business logic. No tables.

### 1.2 `Tenancy`
Workshop/branch entity, configuration (workshop hours, lunch break, address change history), GST registration, T&Cs, document uploads, bank/finance info. Single source for "which workshop am I in".

### 1.3 `Auth`
Login, session control (single-session, half-hour idle logout), biometric login hook, IP-based access, out-of-hours login alert trigger. Uses Fortify.

### 1.4 `Authorization`
Roles, permissions, designation→role auto-mapping, module/report visibility matrix. Powers the "System Control" screen.

### 1.5 `AuditLog`
Activity log (created/modified/deleted by, when), login log, active-session viewer. Subscribes to model events globally; owns no business writes.

### 1.6 `Security`
Module lock, unauthorized-login alert dispatcher, daily auto-backup confirmation/restore status. Operational concerns only — not auth.

---

## 2. Master Data (one module per master family — keeps each tiny and ownable)

### 2.1 `EmployeeMaster`
Employee register, designation, dept, bank/IFSC, contact, KYC docs.

### 2.2 `CustomerMaster`
Customer profile, contact, KYC (Aadhar/PAN/RC) — admin-restricted file access, customer vehicle linkage.

### 2.3 `VehicleMaster`
Brand, Model, Variant, Color, Vehicle Master, Customer Vehicle, Reg-No standard format (`GJ 05 AA 1234`).

### 2.4 `RegionMaster`
State / City / Area / PIN code lookups.

### 2.5 `VendorMaster`
Vendor profile, GSTIN, contact, bank, credit limit, payment terms.

### 2.6 `InsuranceCompanyMaster`
Insurer profile, agent contacts, default pass-percent rules.

### 2.7 `SpareMaster`
Spare catalog, HSN, brand, inventory group/sub-group, location, MIN/MAX, UOM, barcode type, tyre-specific fields (Dimension/Rim Size/LI-SI/Pattern), Excel bulk price update.

### 2.8 `LabourMaster`
Labour catalog, HSN/SAC, dept, OSL flag, rate, Excel bulk price update.

### 2.9 `InventoryGroupMaster`
Inventory groups & sub-groups (Brake / Suspension / etc.). Standalone so it can be reused by Spare, Labour, Estimate templates.

### 2.10 `ServiceTypeMaster`
Service types, departments, frequent/general job descriptions, job-cancel reasons, page list, consumable departments, vehicle inventory checklist items.

### 2.11 `InspectionTemplateMaster`
Inspection item groups, inspection items, inspection templates (PMS/Tyre/Bodyshop/Basic/Custom).

---

## 3. HR Domain

### 3.1 `Attendance`
Punch in/out via mobile app (selfie capture), missed-punch list, late/early lists, in-out report data. **Does not** calculate salary.

### 3.2 `LeaveManagement`
Leave types (CL/SL/PL), application, approval workflow, leave balance, holiday/weekly-off calendar.

### 3.3 `LateMemo`
Late memo generation + dispatch. Separated because it has its own approval+notification flow.

### 3.4 `Payroll`
Basic/gross/deductions/net, PF/ESI, TDS/Professional tax, advance/loan, bonus, salary register/bank transfer, salary slip, CTC. Reads from Attendance + SmartSalary.

### 3.5 `SmartSalary`
KPI/KRI engine — points, deductions, cross/up-sell, discipline, punctuality, uniform, TAT, repeat-job penalty, billing ratio (70/30), feedback weighting. Outputs incentive figures consumed by Payroll.

### 3.6 `TargetIncentive`
Target setup (monthly/quarterly/yearly/custom), category, weightage, achievement tracking, slab/fixed/hybrid incentive payout, approvals.

---

## 4. Front-Desk / CRM Domain

### 4.1 `Appointment`
Booking via app/website/email/call, time slot, auto-assign technician by workload, pickup address.

### 4.2 `PickupDrop`
Scheduling, driver/vendor assignment, status tracking (Scheduled→Picked→In Transit→Delivered).

### 4.3 `GateInOut`
Inward/Outward via ANPR camera + manual fallback, image capture, Reg-No standardization. Pure gate event log.

### 4.4 `CustomerComplaint`
Complaint register (phone/email/website), resolution workflow.

### 4.5 `CustomerFeedback`
Service rating capture (NPS/1-5), sent vs received tracking, negative-action workflow.

### 4.6 `AdvisorFeedback`
Customer-rates-advisor feedback, separate from service rating.

### 4.7 `FollowUp`
Generic follow-up engine: Service Due, Service Recommended, Sales Inquiry, Insurance Renewal, AMC Service Due. One module, polymorphic targets — avoids 5 near-duplicate modules.

### 4.8 `SalesInquiry`
Capture parts/service inquiries from web/phone/customer app. Hands off to FollowUp.

### 4.9 `ServiceReferral`
Customer referral program (web/WhatsApp/email), special/combo offers.

---

## 5. Job & Workflow Domain

### 5.1 `JobCard`
Job card creation, T&C, vehicle inventory snapshot, complaints, repeat-job linkage, vehicle history reference, photos, suggested service, signature, status tree.

### 5.2 `JobCancel`
Cancel-request workflow with standard reasons, admin approval/reject/visit-explain.

### 5.3 `DigitalInspection`
Vehicle inspection checklist execution (PMS/Tyre/Bodyshop/Basic/Custom), image capture, Rep/Adj/OK/IA/FA outcomes.

### 5.4 `VehicleInspectionOrder` (VIO)
Advisor → FI / FI → Technician / Advisor → Technician dispatch + bay & time tracking.

### 5.5 `VehicleInspectionResponse` (VIR)
Technician response with timings, additional findings, parts/labour additions, damage mapping, file share.

### 5.6 `FinalWorkOrder` (FWO)
Final job allotment after estimate approval, cancel-on-not-started, cancel notification.

### 5.7 `FinalWorkResponse` (FWR)
Technician work tracking (start/end/total per item) for FWO.

### 5.8 `FinalInspection`
QC + test drive sign-off, advisor verification of complaint closure.

### 5.9 `JobHistory`
Read-model: status tree, promised vs expected vs delivered, per-job timeline. Pure projection — owns no transactional writes.

---

## 6. Outside Work Domain (separate from internal job flow)

### 6.1 `OutsideWorkInquiry` (OWI)
Inquiry to contractor/vendor for charge & availability (FILM/PADDING etc.).

### 6.2 `OutsideWorkResponse` (OWR)
Capture vendor reply (charge, available time, completion time) via SMS link or web.

### 6.3 `OutsideWorkOrder` (OWO)
Final order to contractor/vendor, cancel-on-not-started.

### 6.4 `OutsideLabourBilling`
Bill receive + verification (advisor SMS verify), entry by store exec (parts+labour), returns, CN/DN.

---

## 7. Parts Procurement Domain

### 7.1 `InternalPartsInquiry` (IPI)
Advisor → store-incharge: stock check, alternatives, brand/warranty, rate.

### 7.2 `InternalPartsResponse` (IPR)
Store-incharge availability response, reservation.

### 7.3 `VendorPartsInquiry` (VPI)
Store-incharge → vendor RFQ; multi-vendor price/brand comparison.

### 7.4 `VendorPartsResponse` (VPR)
Vendor reply capture.

### 7.5 `InternalPartOrder` (IPO)
Pick/issue/hand-over to technician, IPO carry-forward, cancel request, dynamic item-status (no manual tick).

### 7.6 `VendorPurchaseOrder` (VPO)
PO to vendor, >₹25k admin approval, follow-ups/reminders, cancel request, vendor confirmation + dispatch (courier/consignment).

### 7.7 `PurchaseQuoteRequest`
Vendor quote ask + acknowledgement, file attachment.

### 7.8 `PartsReceiving`
GRN — physical + invoice verification, QC mark, advisor notify.

### 7.9 `PartsHandover`
Store → technician hand-over with date/time, separate from receiving.

### 7.10 `Challan`
Delivery challan entry via barcode/QR scan, warranty/guarantee capture.

### 7.11 `Purchase`
Tax Invoice / Bill of Supply entry, barcode print/stick/scan, sales-rate auto-fetch, dept auto-fetch, add-from-Challan, item-wise entry datetime, warranty.

### 7.12 `PurchaseReturn`
Item-wise return (one/all), CN against multiple purchases, barcode-driven return, less-qty partial return, w/o-stockable item handling, RTS settlement.

### 7.13 `GoodsReturnNote`
GRN return memo for vendor, security-gate verification at exit, pending matters tracking.

---

## 8. Inventory Domain

### 8.1 `Inventory`
Stock projection per spare/barcode/location, FIFO layers, MIN/MAX alerts, stock-qty filters (>0, <0, =0, all), Tecdoc integration, VIN/Reg-No-based identification.

### 8.2 `StockCounting`
Quarterly physical-vs-system reconciliation via barcode, mismatch/loss/excess reports.

### 8.3 `StockMovement`
Ledger projection (Purchase / Challan / Pur.Return / Sales / SR / WIP / Consumption / Closing) — read model.

### 8.4 `Consumable`
Consumable usage tracking, painting material ML→GM conversion, job-no-wise consumption, add-from-Purchase.

### 8.5 `Barcode`
Barcode generation, label print, scan resolution. Single owner so all modules consume one API.

---

## 9. Estimate & Approvals Domain

### 9.1 `SalesEstimate`
Regular + Insurance estimate, group/item-wise, template add, group-wise print, insurer-share / customer-share computation.

### 9.2 `EstimateApproval`
Customer digital approval via WhatsApp/email link or self-approval, approved datetime, ins-pass-% propagation to proforma.

### 9.3 `ProformaApproval`
Admin approval for proforma >₹25k (approve / reject / visit-explain).

### 9.4 `InvoiceCorrection`
Invoice/receipt change request → admin response (Reg/Ins/Counter).

### 9.5 `ExcessStockApproval`
Add-from-Proforma/Invoice → admin notification → Pending/Approved/Rejected.

### 9.6 `VPOApproval`
Stock value >₹25k VPO admin approval (kept separate from procurement workflow).

### 9.7 `AdvanceReceiptRequest`
Advisor → customer advance request via link (Regular/Ins).

### 9.8 `AdvancePaymentRequest`
Store-incharge → admin advance-to-vendor request.

---

## 10. Insurance & Bodyshop Domain

### 10.1 `ClaimIntimation`
Register accidental claim, insurer/policy/intimation no., insurer/surveyor trigger.

### 10.2 `SurveyorInspection`
Schedule, surveyor findings, photos, provisional estimate, ReInspection (RI), pass-% link to estimate.

### 10.3 `DocumentCollection`
Collect customer ID/insurance docs (RC/DL/Ins/PUC/Aadhar/PAN/Cheque/Signature), photo capture, scan upload, checklist + acknowledgement slip.

### 10.4 `DocumentDelivery`
Track doc movement customer↔insurer↔workshop with signature confirmation.

### 10.5 `DeliveryOrder` (DO)
Insurance DO request, file attachment (proforma), insurer confirmation to deliver vehicle.

### 10.6 `InsurancePolicy`
Policy entry + renewal (Comprehensive/Third Party), expiry tracking, document attachments. FollowUp engine drives reminders.

---

## 11. Sales & Billing Domain

### 11.1 `Proforma`
Proforma generation, parts add from Purchase/OutsideLabour/Estimate, copy/paste, pre-invoice print (insurance + customer), spares-desc dept suggestion, file upload (scanning report), warranty.

### 11.2 `ProformaCosting`
Authorised-only P&L view per proforma — item-wise purchase rate, paint material, VA, gross P&L. Separate to enforce access boundary.

### 11.3 `RegularSalesInvoice`
Regular + Insurance invoice, easy update for dept/advisor/technician/OSL, invoice split, edit/delete vs proforma reconciliation.

### 11.4 `CounterSalesInvoice`
Counter sales — kept separate (different flow, no job card).

### 11.5 `SalesReturn`
Reg / Ins / Counter return — one-by-one/select-all, barcode return, item-wise entry datetime.

### 11.6 `EInvoice`
IRN/QR generation, government portal sync. Separate so sales modules don't carry tax-portal coupling.

### 11.7 `PrintAll`
Bulk print (invoices, receipts, vehicle history) — selected/all/by-date, PDF download + share.

---

## 12. Cash & Vendor Settlement Domain

### 12.1 `Receipt`
Regular + Counter receipt, advance receipt entry by cashier, redirect-to-Gatepass, multi-invoice settlement.

### 12.2 `ReceiptRefund`
Refund against sales return / overpayment.

### 12.3 `Payment`
Vendor payment, month-wise bulk pay, advance-payment entry by cashier, vendor notification.

### 12.4 `PaymentRefund`
Refund from vendor / to vendor, requested vs refunded datetime.

### 12.5 `OutstandingTracker`
Customer / Insurance Co. / Vendor outstanding projections — read model.

---

## 13. Vehicle Delivery Domain

### 13.1 `GatePassApproval`
Admin approval for delivery without bill/payment, bill-amount threshold rules.

### 13.2 `GatePass`
Gate pass issue — Work Pending (against VPO/observation) vs Final Delivery.

### 13.3 `VehicleOutward`
ANPR-based outward capture, image capture. Closes the GateInOut loop.

---

## 14. Warranty Domain

### 14.1 `CustomerWarrantyClaim`
Repeat-job/warranty/guarantee processing advisor → store-incharge → vendor, status, admin approval, one-side warranty (self/FOC/discount).

### 14.2 `VendorWarrantyClaim`
Lightweight wrapper over GoodsReturnNote — pending/approved/rejected, vendor payment terms.

---

## 15. Service Packages

### 15.1 `ServicePackage`
Combo / AMC package definition, included services, validity. Drives FollowUp + dedicated AMC reminders.

---

## 16. Accounting Domain (split — Accounting itself is too big as one module)

### 16.1 `ChartOfAccounts`
Master accounts, groups (Asset/Liability/Equity/Income/Expense).

### 16.2 `Ledger`
Journal posting engine — every transactional module posts via this.

### 16.3 `BankCashBook`
Bank/cash ledgers, deposits/withdrawals, cheque entries, BRS.

### 16.4 `Expense`
Day-to-day expense, petty cash book, cash hand-over, reimbursement, expense approvals.

### 16.5 `GstFiling`
Input/output GST, GSTR-1, GSTR-2B, GSTR-3B, GSTR-9, B2B/B2CS, HSN-wise, GSTR2 reconciliation.

### 16.6 `FinancialStatements`
Trial Balance, P&L, Balance Sheet, Cash Flow.

### 16.7 `AssetManagement`
Tools/equipment register, depreciation, service-cost allocation.

### 16.8 `TaxCompliance`
TDS, GST audit trail, account audit reports.

### 16.9 `CreditLimit`
Customer & vendor credit limit enforcement.

---

## 17. Reporting & Analytics

### 17.1 `Dashboard`
Role-based dashboard widgets (charts, KPIs, redirect links).

### 17.2 `Reporting`
Generic report engine — filters, export (Excel/PDF), drag-drop column ordering. All report definitions registered here.

### 17.3 `AnalyticsKpi`
KPI/BI aggregations — daily/weekly/monthly/quarterly/yearly comparisons, dept/advisor/technician slicing.

### 17.4 `MisReport`
MIS / Daily auto-email reports — separate scheduler-driven module.

### 17.5 `MasterSearch`
Multi-module global search — status / date / Reg-No / customer / contact, count + redirect, reverse warranty tracking. Read-only across all modules.

---

## 18. Communications

### 18.1 `Notification`
Channel-agnostic dispatcher (WhatsApp / Email / SMS) — auto + manual, custom SMS, block/unblock per template, send-one or bulk.

### 18.2 `NotificationTemplate`
Template registry per event (Appointment / VIO / FWO / Invoice / Receipt / Follow-up / etc.) with merge fields.

### 18.3 `WhatsAppGateway`
WhatsApp provider adapter.

### 18.4 `EmailGateway`
Email provider adapter.

### 18.5 `SmsGateway`
SMS provider adapter.

---

## 19. External Integrations (each isolated for swap-ability)

### 19.1 `PaymentGateway`
Razorpay integration, advance-link generation, webhook ingest.

### 19.2 `CameraIntegration`
ANPR + bay-wise camera session access (customer-shareable).

### 19.3 `TecdocIntegration`
Spare identification by VIN / Reg-No / vehicle.

### 19.4 `BiometricIntegration`
Fingerprint / face login adapter.

### 19.5 `AiAssistant`
AI suggestion endpoints (parts suggestion, follow-up drafts, anomaly detection).

---

## 20. Mobile / Customer-Facing

### 20.1 `CustomerApp`
Customer-facing API surface (Android/iOS) — appointment booking, status tracking, document upload, approvals, receipts, sharing.

### 20.2 `EmployeeApp`
Employee-facing API surface — attendance, technician work updates, advisor on-floor flows.

### 20.3 `PublicApi`
Versioned API resources used by both apps + the website inquiry forms.

---

## 21. Operational

### 21.1 `Backup`
Daily auto-backup execution + restore + confirmation status (consumed by Security alerts).

### 21.2 `Scheduler`
Cron registry — feedback dispatch, follow-up reminders, daily MIS email, service-due reminders (3-times rule), insurance-renewal nudges, alignment-due SMS.

### 21.3 `MenuPreferences`
Per-user menu pin (drag/drop), ordering, recently used. Required by every screen but owns its own table.

### 21.4 `ComplaintRegister`
Internal technician/general complaint register (distinct from CustomerComplaint).

### 21.5 `KeyboardShortcuts`
Per-user / per-module shortcut config.

---

## Module Count Summary

| Layer | Count |
|---|---|
| Foundation | 6 |
| Master Data | 11 |
| HR | 6 |
| CRM / Front-Desk | 9 |
| Job Workflow | 9 |
| Outside Work | 4 |
| Parts Procurement | 13 |
| Inventory | 5 |
| Estimate & Approvals | 8 |
| Insurance & Bodyshop | 6 |
| Sales & Billing | 7 |
| Cash & Settlement | 5 |
| Vehicle Delivery | 3 |
| Warranty | 2 |
| Service Packages | 1 |
| Accounting | 9 |
| Reporting | 5 |
| Communications | 5 |
| Integrations | 5 |
| Mobile/API | 3 |
| Operational | 5 |
| **Total** | **127** |

---

## Cross-Cutting Conventions

- Every transactional module emits domain events consumed by `Ledger`, `AuditLog`, `Notification`, `AnalyticsKpi`, `SmartSalary`.
- Every transactional record carries: auto-generated ID (shown in success msg + search), entry date/time, capital-only typing enforced at request layer.
- Approval-flow modules (`*Approval`, `JobCancel`, `InvoiceCorrection`) share a `Core\Approvable` contract.
- Read-model modules (`JobHistory`, `OutstandingTracker`, `StockMovement`) own no writes; rebuilt from event stream.
- `MasterSearch` indexes via a registered contract each module implements — no direct cross-module queries.

---

## Phasing Suggestion (matches "Priority 1st" in requirements)

**Phase 1 (1st priority):** Tenancy, Auth, Authorization, all Masters, Attendance, Payroll, SmartSalary, Appointment, PickupDrop, GateInOut, JobCard, DigitalInspection, IPI/IPR/IPO/VPI/VPR/VPO, Inventory, Challan, Purchase, PurchaseReturn, SalesEstimate, EstimateApproval, Proforma, ProformaApproval, all Sales Invoices, SalesReturn, Receipt, Payment, GatePass, VehicleOutward, CustomerFeedback, FollowUp (service due/recommended), Notification + gateways, Reporting, Dashboard, MasterSearch, MenuPreferences.

**Phase 2 (2nd priority):** OWI/OWR/OWO, OutsideLabourBilling, ClaimIntimation, SurveyorInspection, DocumentCollection/Delivery, DeliveryOrder, JobCancel, AdvanceReceipt/Payment workflows, GoodsReturnNote, FinalInspection, ExcessStockApproval, InvoiceCorrection, CustomerWarrantyClaim, VendorWarrantyClaim, ServicePackage, Accounting (full), CRM follow-ups, AdvisorFeedback, CustomerComplaint, GatePassApproval, EmployeeApp, CustomerApp, PaymentGateway, CameraIntegration.

**Phase 3 (3rd priority):** StockCounting, AiAssistant, advanced analytics, BiometricIntegration, TecdocIntegration.
