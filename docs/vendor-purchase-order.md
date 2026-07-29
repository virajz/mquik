# VPO (Vendor Purchase Order) + VPR (Vendor Purchase Response) — modules 38 & 39

The confirmed **order** placed on a vendor after inquiry/approval, with the vendor's **response**
(acknowledgement + dispatch) folded into the same record — so **module 39 (VPR) needs no separate
module**. Reshaped from module 17 (VPI): header + ordered lines + charges + attachments.

## What's done

`app/Modules/VendorPurchaseOrder/` (`VPO-#####`, group **Inventory**, route `vendor-purchase-order.index`)
- Parent `vendor_purchase_orders` + `vendor_purchase_order_items` + `vendor_purchase_order_charges` +
  `vendor_purchase_order_attachments`.
- **Sources:** VPI (17), VPO Approval (35), Advance Payment (37) references; vendor + vendor type,
  job card, priority, raised-by employee.
- **Line items:** spare (auto-fills brand/type/uom/hsn/tax/rate), qty, rate, discount, warranty,
  lead time, **closing stock** reference.
- **Delivery & terms:** payment term, delivery mode, transport/logistics company (a VendorMaster),
  delivery commitment (+ custom days), expected delivery date, T&Cs.
- **Vendor response (VPR, module 39):** `acknowledgement_status` (Pending / Accepted / Rejected —
  rejection reason reveals on Rejected), `courier_company`, `consignment_no`, `consignment_date`.
- **Status:** pending / acknowledged / dispatched / delivered / cancelled (cancellation reason +
  note reveal on Cancelled).
- Attachment types: PO Copy / Dispatch Copy / Invoice Copy / Photo Evidence.
- **Index**: KPIs (Pending / **Delayed** = open past expected delivery / Cancelled), filters by status
  + acknowledgement + vendor, CSV **Purchase Order (MSQ) Report**.
- Tests: **12 green**.

## How to visually test on the UI

1. **Inventory → Vendor Purchase Orders → New PO.** Set **PO Type**, pick a **Vendor** (required),
   optionally link a **VPI / Approval / Advance Payment**.
2. Add **ordered parts** — pick a spare to auto-fill brand/uom/tax/rate; set qty/rate/discount/warranty
   and **closing stock**. Add a charge.
3. **Delivery & Terms:** choose delivery mode + a **transport company**; set **Delivery Commitment =
   Custom** → custom-days reveals.
4. **Vendor Response:** set Acknowledgement = **Accepted**, status = **Dispatched**, fill courier +
   consignment. Try Acknowledgement = **Rejected** → rejection reason reveals.
5. Attach a **Dispatch Copy**; save. Index → **Purchase Order (MSQ) Report** exports the CSV; the KPI
   cards (incl. **Delayed**) and status/acknowledgement filters work.

## Related modules impacted

- **Reused (no changes):** VendorMaster (also as transport company), VendorTypeMaster,
  VendorPurchaseInquiry (17), VpoApproval (35), AdvancePayment (37), SpareMaster, brand/part/uom/hsn/
  tax masters, ChargeTypeMaster, JobCard, PriorityMaster, EstimateRevisionReasonMaster (cancellation).
- New permissions synced; menu under **Inventory** (beside VPI).

## Effect on the system

Closes the purchasing chain **VPI (17) → VPO Approval (35) → VPO (38) + VPR response**. The vendor
response lives on the PO header, so acknowledgement and dispatch are tracked without a second module.
Additive — no existing module changed.

## Design note (interpretation — flag)

Module 39 (VPR) was folded into the VPO record as acknowledgement + dispatch fields, per the spec's
"No separate module required — covered in above". If you'd rather VPR be a distinct response entry
(one PO → many partial dispatches), it should become its own child table — say the word.
