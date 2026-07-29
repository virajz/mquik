# Advance Payment Entry — module 37

Accounts/cashier records an **advance payment made to a vendor** — the money-out mirror of module 26
(Advance Receipt Entry). Uses its **own** FY series `MQ/AP/26-27/#####` (via a persisted `fy_label`,
deliberately not sharing any other module's counter). Reuses the payment-mode / bank / cheque-bounce
masters.

## What's done

`app/Modules/AdvancePayment/` (`MQ/AP/<FY>/#####`, group **Finance**, route `advance-payment.index`)
- Parent `advance_payments` + child `advance_payment_attachments`.
- Links the module-36 **advance request**, **VPI**, job card / customer / vehicle / dept / service
  type, vendor, bank, payment mode, and entry-by / store-incharge / advisor employees.
- Enums: `paymentStatuses` (posted / cancelled / reversed), `advancePaymentTypes` (against_request /
  direct / odd_item), `chequeStatuses` (issued / cleared / returned_bounced / cancelled),
  `reversalReasons`, `cancellationReasons`, `attachmentTypes`.
- **Reveal logic:** reversal reason on `reversed`, cancellation reason on `cancelled`, cheque-bounce
  reason on cheque status `returned_bounced`. Reference/cheque no upper-cased on save.
- **Index**: KPIs (Posted / Cancelled / Reversed + posted value), CSV **Payment Report**.
- Tests: **13 green** (covers the FY-series increment).

## How to visually test on the UI

1. **Finance → Advance Payment Entry → New Payment.** Pick a **Vendor** (required), **advance type**,
   **amount**, **payment mode** (required); optionally the **advance request** / **VPI** references.
2. For a cheque, set cheque no/date and **Cheque Status = Returned / Bounced** → bounce reason reveals.
3. Set **Payment Status = Reversed** → reversal reason reveals; **Cancelled** → cancellation reason.
4. Save — the number is `MQ/AP/<FY>/00001` (increments per financial year). Index → **Payment Report**
   exports the CSV; KPI cards filter.

## Related modules impacted

- **Reused (no changes):** VendorAdvanceRequest (36), VendorPurchaseInquiry (17), PaymentModeMaster,
  BankMaster, ChequeBounceReasonMaster, JobCard, Customer/Vehicle, EmployeeMaster. `App\Support\
  FinancialYear` for the series.
- New permissions synced; menu under **Finance**.

## Effect on the system

Completes the vendor-advance chain **request (36) → payment (37)** and is the source an eventual VPO
(38) points back to via `advance_payment_id`. Additive — no existing module changed.
