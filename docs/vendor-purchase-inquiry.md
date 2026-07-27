# Vendor Purchase Inquiry (VPI) / RFQ — module 17

The store asks a vendor for part **rate, brand, delivery time, warranty and payment terms** —
a Request for Quotation, with quote tracking for comparison. The vendor-facing counterpart to
IPI (module 14).

## What's done

**New transactional module** `app/Modules/VendorPurchaseInquiry/`
- Parent `VendorPurchaseInquiry` (`vendor_purchase_inquiries`), auto-numbered `VPI-#####`.
  - Inquiry type (Against Job Card / Stock Replenishment / Expense), vendor, job card, raised-by,
    priority, vendor category (Preferred/Approved/Backup), vendor rating dimension
    (Quality/Price/Delivery/Support), payment terms (Advance/COD/7-15-30 Days Credit),
    comparison parameter (Price/Discount/Lead Time/…/Landed Cost/Warranty/Payment T&C), approval
    authority, TAT (+ custom days), revision reason, 4-state status, T&Cs/SLA + notes.
- Child `VendorPurchaseInquiryItem` (part + vendor quote per line): spare, brand, inventory type
  (PartTypeMaster = Genuine/Aftermarket), UOM, HSN, tax, vehicle variant, qty, **quoted rate**,
  **discount** (Line/Scheme/Cash + value), **warranty** (type + period value & unit — months OR
  days), **lead time (days)**, **stock status** (Partially/Fully/Not Available), alternative option
  (Primary/Alternate Part/Alternate Brand). Picking a spare auto-fills brand/type/UOM/HSN/tax/rate.
- Child `VendorPurchaseInquiryCharge` — additional charges (Freight/P&F/Transport/Packing/Handling
  via ChargeTypeMaster + amount).
- Child `VendorPurchaseInquiryAttachment` — RFQ document / vendor quotation / approval note (PDF/image).
- Create/edit **page** + Index with 4-tile dashboard (Pending / In Progress / Completed / Cancelled),
  search, status/type/vendor filters.

**Report** `PurchaseReport` (`/purchase-report`) — filterable (status/type/vendor/date) read-only
screen + CSV export.

Tests: VPI 14, report 6 — all green.

## How to visually test on the UI

1. **Inventory → Vendor Purchase Inquiries → New RFQ.**
2. Set **Inquiry Type**, pick a **Vendor** (required), **Priority**, optionally **Vendor Category**
   and **Rating On**.
3. **Parts & Quote**: search-pick a **Spare** → brand/type/UOM/tax/rate auto-fill. Fill **Quoted
   Rate**, **Discount** (type + value), **Warranty** (type + period, toggle months/days), **Lead
   Time**, **Availability**, **Option**. Add a second part line.
4. **Additional Charges**: Add charge → pick Freight/Transport/etc. + amount. Empty rows are dropped.
5. **Terms & Comparison**: pick **Payment Terms** and **Compare Quotes By**; set TAT = *Custom* →
   confirm the custom-days field reveals; add **T&Cs/SLA** text.
6. **Attachments**: Add file → upload a PDF/image, set type = *Vendor Quotation*.
7. **Status** + optional revision reason → Save → back on index with a `VPI-#####` row; dashboard
   tiles filter by status.
8. **Inventory → Purchase Report** → filter and **Export CSV**.
9. **Cross-vendor comparison:** raise one VPI per vendor for the same requirement, then compare them
   in the Purchase Report (filter by job card / type).

## Related modules impacted

- **Reused (no changes):** VendorMaster, JobCard, EmployeeMaster, PriorityMaster, SpareMaster,
  SpareBrandMaster, UnitOfMeasureMaster, HsnMaster, TaxMaster, PartTypeMaster, VehicleVariantMaster.
- **Seeders extended:** `ChargeTypeMaster` (+ TRANSPORT, PACKING, HANDLING),
  `EstimateRevisionReasonMaster` (+ QUANTITY CHANGE, SPECIFICATION CHANGE, DISCOUNT UPDATE) — reused
  as the additional-charge heads and revision reasons rather than new masters.
- No new master needed — all other vocabularies (warranty, payment terms, vendor category/rating,
  discount, comparison, attachment type, inquiry type, status, TAT) are enums on the models.
- Permissions registered via `php artisan auth:sync-permissions`.

## Effect on the system

Gives the store a structured RFQ workflow: ask a vendor, capture their full quote (rate, discount,
warranty, lead time, charges, terms) against requested parts, attach the RFQ/quotation/approval
documents, and track status. Reuses SpareMaster commercial defaults so quotes stay consistent with
the catalogue. Additive — no existing screen changed behaviour.

## Design notes

- **Single-vendor RFQ header** (mirrors IPI). One VPI = one vendor's quote. Multi-vendor comparison
  is done by raising a VPI per vendor and comparing in the Purchase Report — rather than a
  header→per-vendor-quote nested structure, which would be the heavier alternative if true
  side-by-side comparison in one screen is needed later.
- **Warranty period** modelled as value + unit (month/day) to honour "select in months or days",
  instead of the canned 3m/6m/12m/24m enum used elsewhere.
- Line-level vehicle applicability uses a single `vehicle_variant_id` FK (encodes
  brand→model→transmission→fuel), consistent with IPI and SpareMaster.
