# Payment Refund Request / Response — module 65

A vendor refunds money back to the workshop — excess / duplicate payment, cancelled PO, invoice
revision, or a spares / outside-labour purchase return. Records the refund against its source
references, the mode / bank / cheque, and the request → refunded / rejected / cancelled lifecycle.
Header + document attachments (no line items).

## What's done

`app/Modules/PaymentRefund/` (`PRF-#####`, group **Finance**, route `payment-refund.index`)
- Parent `payment_refunds` + `payment_refund_attachments`.
- Enums: `refundAgainstOptions` (advance / regular payment), `refundTypes` (advance-security / excess /
  duplicate / order-cancel / invoice-revision / spares- or labour-purchase-return / compensation),
  `priorities`, `refundModes` (cash / cheque / neft / rtgs / imps / upi / bank_transfer),
  `chequeStatuses` (cleared / bounce), `statuses` (requested / on_hold / refunded / rejected /
  cancelled), and the `rejectionReasons` / `holdReasons` / `cancellationReasons` sets.
- **Source references:** Advance Payment (37, amount auto-fills from it), Vendor PO (38), Goods Return
  Note (51, spares purchase return), Outside Labour Return (43), plus free-text regular-payment and
  purchase-invoice references and a job card. Cheque bounce reuses the ChequeBounceReasonMaster; bank
  reuses BankMaster.
- **Reveal / conditional:** cheque-bounce reason required when cheque status is *Bounce*; hold /
  rejection / cancellation reason required on the matching status.
- **Timestamps** stamped automatically: `requested_at` on create; `refunded_at` / `rejected_at` the
  first time the status becomes refunded / rejected. UTR / cheque / references upper-cased on save.
- **Index**: KPIs (Pending Vendor Refunds / Refund Received Today / **Refund Value This Month**), CSV
  **Payment Report**.
- Tests: **11 green**.

## How to visually test on the UI

1. **Finance → Payment Refund → New Refund.** Pick the **Vendor** (required), the **refund against**
   (advance / regular) and **refund type**; link the **Advance Payment** (amount auto-fills), PO, or a
   Goods Return Note / Outside Labour Return.
2. Enter the **Refund Amount**, **Refund Mode** and bank; for a cheque, set cheque no/date and
   **Cheque Status = Bounce** → the bounce reason becomes required.
3. Set **Refund Status = Refunded** (stamps refunded-at); *On Hold* / *Rejected* / *Cancelled* each
   reveal their required reason. Attach the excess-payment / UTR / cheque / advice proof; save.
4. Index shows pending / received-today counts and this-month refund value; **Payment Report** exports
   the CSV.

## Related modules impacted

- **Reused (no changes):** AdvancePayment (37), VendorPurchaseOrder (38), GoodsReturnNote (51),
  OutsideLabourReturn (43), VendorMaster, EmployeeMaster, BankMaster, ChequeBounceReasonMaster, JobCard.
- New permissions synced; menu under **Finance**.

## Effect on the system

Closes the money-back side of the vendor payment lifecycle — the inbound counterpart to Advance Payment
(37), tying refunds to their originating PO / advance / purchase-return. Additive — no existing module
changed.

## Design note (interpretation — flag)

`refund_mode`, `cheque_status` and the reason sets are enums matching the spec's exact wording (cheque
bounce reason, however, reuses the existing ChequeBounceReasonMaster for consistency with Advance
Payment). Customer / vehicle are stored on the record but the create form surfaces the **job card** as
the primary customer-side reference rather than separate customer/vehicle pickers — say the word if you
want those exposed too.
