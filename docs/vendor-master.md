# Vendor Master (profile extension) — module 97

The existing `VendorMaster` (code/name, multi vendor-types, parts-brand & inventory-group pivots, service
specialities, region/bank FKs, KYC files, terms child, import/export, quick-create) was **extended in place**
to cover the fuller vendor profile in the spec. Only the genuinely-missing pieces were added; the working UI
and all 45 prior tests are untouched.

## What's added (extension)

`app/Modules/VendorMaster/` (route `vendor-master.index`, group **Vendors**)
- **Profile fields:** `legal_name` (Trade Name stays `name`), `registration_date`, `reference`,
  `contact_person1/2`, `branch_address`, `pincode` (re-added), `udyam_no`.
- **Business & classification enums:** `classification` (Manufacturer / Distributor / Dealer / Wholesaler /
  Retailer / Importer), `constitution` (Proprietorship / Partnership / LLP / Pvt Ltd),
  `gst_registration_type` (Regular / SEZ / Export / Composition / Unregistered),
  `msme_type` (Micro/Small/Medium/Enterprise), `msme_activity` (Trading/Services/Manufacturing),
  `vendor_category` (Genuine / OEM / Aftermarket / Scrap-Refurbished).
- **Lifecycle & rating:** `vendor_status` (Active / Inactive / Prospect / Suspended / Blacklisted / Closed)
  with `blacklist_reason` (required when Blacklisted), and a 1–5 `rating`.
- **Commercials:** `payment_terms` (Advance / Cash / Immediate / 7–60 Days — the column never actually
  existed before; added) and `delivery_method` (Self Pickup / Vendor Delivery / Courier / Transport).
- **Performance scorecard:** `on_time_delivery_percent`, `parts_return_percent`, `return_rejection_percent`,
  `avg_response_hours`.
- **T&Cs type** on the terms child: `term_type` (Payment / Delivery / Return / Warranty Policy).
- **Documents child** (`vendor_attachments`): GST certificate / PAN card / MSME certificate / cancelled
  cheque / bank passbook / signed T&Cs (jpg/png/webp/pdf) — a general multi-doc set alongside the existing
  single Aadhaar/PAN KYC uploads.
- Tests: **49 green** (45 existing preserved + 4 new: extended-profile persist, blacklist-reason requiredIf,
  typed term, document attachment).

## How to visually test on the UI

1. **Vendors → Vendors → New Vendor.** Identity now has Trade + **Legal Name**, registration date, reference.
   Contact adds two contact persons. Address adds **Branch Address** + **Pincode**. KYC adds **GST
   Registration Type** + **Udyam No**.
2. New **Business & Classification** section (classification / constitution / category / MSME type &
   activity). Credit adds **Payment Terms** + **Delivery Method**. New **Documents** section (add GST cert
   etc.) and **Performance & Rating** section (star rating + the four %/time metrics).
3. **Notes & Status:** pick a **Vendor Status**; choose *Blacklisted* → **Blacklist Reason** appears and is
   required. Terms rows now carry a **Policy Type**. Save and reopen to confirm round-trip.

## Related modules impacted

- **Reused (no changes):** VendorTypeMaster, SpareBrandMaster, InventoryGroupMaster, ServiceSpecialistMaster,
  GstTypeMaster, RegionMaster, BankMaster.
- **No route/permission changes** — same `vendor_master.*` perms and Vendors menu entry. Existing
  import/export + KYC-file controller untouched.

## Effect on the system

The vendor master now carries the full compliance / classification / commercial / performance profile the
spec asks for, additively — new columns + one new child table (`vendor_attachments`) + one new column on
`vendor_terms`. Existing vendor data, pivots and the KYC flow are unchanged.

## Design note (interpretation — flag)

- **`is_active` vs `vendor_status` are kept independent** on purpose: `is_active` remains the operational
  picker-visibility flag used across POs/bills; `vendor_status` is the richer lifecycle label. They are not
  auto-coupled (coupling would have silently flipped the `inactive()` factory and existing tests). Wire a
  derive-on-save if you want blacklisted/closed to auto-hide the vendor.
- **Service Type** (Parts Supplier / OSL / Contractor / Logistics / Courier) and **Service Specialities**
  (Denting / Painting / …) map to the existing `vendor_types` and `service_specialists` pivots — not
  duplicated. **Dealing Inventory Groups** and **Spares Brands** are the existing multi-pivots.
- **Performance KPIs are manual summary fields**, not computed — there's no PO/GRN/return transaction feed
  wired yet. The spec's **Dashboard KPIs** (Total / Active / New / Blacklisted / Top & Poor performers /
  warranty-claim & return %) belong on an index dashboard / analytics report and are **not** built here; the
  underlying columns (vendor_status, rating, the performance %s) now exist to drive them.
- `payment_terms` needed a real column — an earlier migration only re-added it inside its `down()`, so it had
  never existed; a follow-up migration adds it.
