# Goods Receive & Verification (GRN) — module 45

The store receives vendor parts (against a PO or direct), physically verifies each line against the
order, does QC and **per-line approval** (part / qty / rate / discount / TAT), and allocates storage.
Header + item-wise lines (each with material condition, physical-verification outcome, damage type,
storage bin, per-line approval and a spare photo) + footer evidence attachments.

## What's done

`app/Modules/GoodsReceipt/` (`GRN-#####`, group **Inventory**, route `goods-receipt.index`)
- Parent `goods_receipts` + `goods_receipt_items` + `goods_receipt_attachments`.
- **Sources:** against a **Purchase Order** (38) — required when receipt type is `against_po` — or a
  **Direct Receipt**; optional **Purchase Inquiry** (17) ref. Store received-by / verified-by employees.
- **Per line:** job card, spare (auto-fills brand/uom/rate/last-purchase), vehicle, department,
  floor received-by (technician) + verified-by (advisor), `materialConditions` (new / used /
  refurbished / repairable / repaired / scrap), `physicalVerifications` (ok / excess / less / damage /
  wrong part / mfg defect / missing / expired / packaging), `damageTypes`, **storage bin**, and the
  **per-line approval** block (part approved, qty/rate/discount approved, TAT, last-purchase
  price/vendor/date) + a **spare photo**.
- Enums: `receiptTypes`, `deliveryPerformances` (on time / delayed), `approvalAuthorities`
  (store executive / parts manager / store manager), `vendorCategories`, `vendorRatingTypes`,
  `statuses` (verification_pending / verified / mismatch_accepted / mismatch_rejected). Footer
  attachment types: damage photo / fault evidence / invoice copy.
- **Index**: KPIs (Today's Receipts / Verification Pending / **Rejected Materials** = line count with a
  bad physical-verification outcome), CSV **Purchase Report**.
- Tests: **10 green**.

## How to visually test on the UI

1. **Inventory → Goods Receive & Verification → Receive Goods.** Set **Receipt Type = Against PO** (the
   **Purchase Order** field appears and is required) or **Direct Receipt**; pick the **Vendor** and
   store received/verified employees.
2. Add received lines — pick a spare (auto-fills), set qty/rate, **material condition**, **physical
   verification** (e.g. Physical Damage → Damage Type), a **storage bin** (A1…), the floor
   technician/advisor, the **per-line approval** (qty/rate approved, Part Approved), and upload a photo.
3. Set approval authority + **GRN Status**; add a damage-photo evidence; save.
4. Index KPIs show today's receipts, pending and rejected-material line count; **Purchase Report**
   exports the CSV.

## Related modules impacted

- **Reused (no changes):** VendorPurchaseOrder (38), VendorPurchaseInquiry (17), VendorMaster,
  SpareMaster + brand/uom masters, JobCard, CustomerVehicleMaster, WorkshopDepartment, EmployeeMaster,
  FollowUpModeMaster.
- New permissions synced; menu under **Inventory**. It provides the `goods_receipts` table that the
  handover module (46) references.

## Effect on the system

Adds the inbound-goods gate: parts are received, QC'd and approved line-by-line against the PO before
they enter stock. Additive — no existing module changed.
