# VPO (Vendor Purchase Order) Cancel Request / Response — module 40

The store asks a vendor to cancel a purchase order (full / partial / qty reduction) and tracks the
vendor's response, cancellation-charge terms, advance-refund handling and dispatch-stage rejection.
Reshaped from the VPO (38) transactional template plus the cancel-approval enum style of module 33.

## What's done

`app/Modules/VpoCancelRequest/` (`VCR-#####`, group **Purchase**, route `vpo-cancel-request.index`)
- Parent `vpo_cancel_requests` + item-wise `vpo_cancel_request_items` (each pointing at the PO / job
  card / spare, with `quantity_to_cancel`) + `vpo_cancel_request_attachments`.
- Enums: `requestTypes` (full / partial_item / quantity_reduction), `cancellationReasons`,
  `vendorCategories`, `statuses` (requested_to_vendor / vendor_reviewing / vendor_accepted /
  vendor_rejected / partially_accepted / fully_accepted / cancelled), `cancellationTerms`
  (no_charge / fixed_charge / percentage_charge + charge amount), `advancePaymentStatuses` (the
  6-state refund lifecycle), `holdReasons`, `rejectionReasons` (packed / picking_started / in_transit
  / already_dispatched — why it's too late to cancel), `vendorRatingTypes`.
- **Reveal / conditional:** rejection reason required when vendor rejects; charge amount required for
  a fixed/percentage term (and cleared for no-charge). Spare pick auto-fills brand/uom/hsn/tax/rate.
- **Index**: KPIs (Open Cancellation Requests / Vendor Pending Responses / Refund Pending Cases),
  CSV **Purchase Report**.
- Tests: **11 green**.

## How to visually test on the UI

1. **Purchase → VPO Cancel Requests → New Cancel Request.** Pick a **Vendor** (required), the **PO**,
   a **cancellation type** and **reason**.
2. Add cancellation lines — pick a spare (auto-fills), set **Ordered Qty** and **Qty to Cancel**, and
   the per-line PO / job card / department / advisor.
3. Set **Cancellation Term = Fixed Charge** → the charge field appears (required). Set **Status =
   Vendor Rejected** → the vendor rejection reason appears (required).
4. Set an **Advance / Refund Status**; attach a dispatch challan / refund receipt; save.
5. Index KPIs show open / pending-response / refund-pending counts; **Purchase Report** exports CSV.

## Related modules impacted

- **Reused (no changes):** VendorPurchaseOrder (38), VendorMaster, JobCard, WorkshopDepartment,
  EmployeeMaster, SpareMaster + brand/uom/hsn/tax masters, PriorityMaster, FollowUpModeMaster,
  VehicleVariantMaster.
- New permissions synced; menu under **Purchase**.

## Effect on the system

Gives a placed PO (38) a governed cancellation + refund path, closing the purchase lifecycle
(inquiry → approval → order → **cancel**). Additive — no existing module changed.
