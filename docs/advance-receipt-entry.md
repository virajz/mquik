# Advance Receipt Entry — module 26

Cashier manually records an advance payment against a job card / estimate, with an FY-based receipt
series, payment mode, bank/cheque tracking and proof attachments.

## What's done

**New module** `app/Modules/AdvanceReceipt/` — FY series **`MQ/AR/26-27/#####`**.
- Parent `advance_receipts`: job card, estimate ref, **advance-request** link (module 25), insurer,
  customer, vehicle, department, service type, received-by employee; **payment mode** (reused
  PaymentModeMaster), **bank**, **amount**, **payment status** (Partially/Fully Received / Cancelled /
  Failed / Refunded), reference/UTR no; **cheque** no/date/status (Received/Deposited/Cleared/
  Returned/Cancelled) + **cheque bounce reason** (revealed when Returned); **cancellation reason**
  (required when Cancelled); **difference amount + reason**; received-at.
- Attachment child `advance_receipt_attachments` with **type** (Cheque Copy / UTR Screenshot /
  Deposit Slip / Payment Advice), PDF/image.
- Index with 4 KPIs (Active count, ₹ Advance Received, Partially Received, Cancelled/Failed), search,
  status + mode filters, and the **Receipt Report** (CSV export).

Tests: 13 green — including the FY series format and the **per-financial-year sequence increment**.

## How to visually test on the UI

1. **Finance → Advance Receipt Entry → New Receipt.**
2. Link Job Card + Estimate, pick Customer/Vehicle/Insurer/Department/Received By.
3. **Payment**: enter **Amount**, pick **Payment Mode** = *Cheque* → the **Cheque** section reveals →
   fill cheque no/date and set **Cheque Status** = *Returned* → **Cheque Bounce Reason** reveals.
4. Set **Payment Status** = *Cancelled* → **Cancellation Reason** reveals and is required. Enter a
   **Difference Amount** + reason if short/excess.
5. **Attachments**: Add file → pick type *Cheque Copy* / *UTR Screenshot* and upload.
6. Save → the receipt gets an `MQ/AR/<FY>/#####` number; **Report** exports the filtered CSV.

## Related modules impacted

- **Reused (no changes):** `App\Support\FinancialYear` (FY label), JobCard, SalesEstimate,
  AdvanceReceiptRequest (25), InsuranceCompanyMaster, CustomerMaster, CustomerVehicleMaster,
  WorkshopDepartmentMaster, ServiceTypeMaster, EmployeeMaster, PaymentModeMaster, BankMaster,
  ChequeBounceReasonMaster, ReceiptCancellationReasonMaster, ReceiptDifferenceReasonMaster.
- **Seeder extended:** PaymentModeMaster (+ CREDIT CARD, DEBIT CARD, NEFT, RTGS, IMPS, RAZORPAY).
- New permissions synced; menu under **Finance**.

## Effect on the system

Turns the advance request (25) into an actual money-received record with a proper FY receipt number
and full cheque/UTR audit trail. Additive.

## Design notes

- **Deliberate prefix deviation:** the spec illustrates `MQ/RR/26-27/0708`, but `MQ/RR/` is already
  owned by the existing **RegularReceipt** module and shares one running sequence. To avoid colliding
  with that counter, advance receipts use **`MQ/AR/`** with their own per-FY sequence (same
  `FinancialYear::label` + `fy_label` count()+1 mechanic as RegularReceipt). A standalone module also
  keeps the billing module untouched.
- Payment status is a new advance-specific enum; **cheque status** and **attachment type** mirror
  RegularReceipt's values exactly (reused shape). The "Receipt Report" is the Index's filtered CSV
  export.
