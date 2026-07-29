# Goods Return Note (Request / Response) — module 51

Return excess, incorrect, defective or warranty **parts / labour** to a vendor. A **single** module for
both parts and labour returns (same warranty-claim workflow as module 43), with the vendor accepting,
rejecting or counter-proposing. Header + item-wise return lines (spare **or** labour, each with
before/after photo evidence + OL-bill ref) + document/evidence attachments.

## What's done

`app/Modules/GoodsReturnNote/` (`GRTN-#####`, group **Inventory**, route `goods-return-note.index`)
- **Cloned from module 43 (Outside Labour Return / Warranty)** — the spec's field list is identical —
  then re-pointed to its own tables (`goods_return_notes` + `goods_return_note_items` +
  `goods_return_note_attachments`), doc prefix `GRTN-`, and the **Purchase Return Report**.
- Same structure: `return_type` (regular / warranty), `claim_type` (labour+parts / parts), the merged
  labour+parts `return_reasons`, warranty type/period, rework type, TAT (custom), the 12-state claim
  `status` (requested → under_review → partially/fully_accepted → rework/replacement_in_progress →
  counter_proposal → cn/dn/replacement_adjusted → rejected / cancelled), counter proposals, rejection
  reasons, reminder frequencies (config-only), vendor rating types. Per line: item type (spare/labour),
  spare + brand/uom/hsn/tax, material condition, before/after photo, OL-bill ref. Attachment types:
  vendor bill copy / warranty card / complaint / damage photo / work order copy / inspection report +
  the additional views.
- **Reveal / conditional:** counter proposal on `counter_proposal`, rejection reason on `rejected`,
  custom TAT / reminder days on `custom`. Spare pick auto-fills the line.
- **Index**: KPIs (Open Warranty Claims / Recovery Amount Pending), CSV **Purchase Return Report**.
- Tests: **11 green**.

## How to visually test on the UI

1. **Inventory → Goods Return Note → New Return.** Set **Return Type = Warranty**, a claim type + reason,
   pick the **Vendor** (required), sales invoice, customer + vehicle.
2. Add return lines — flip **Item Type** between Spare and Labour, set qty/rate, material condition, the
   **OL Bill** ref, and upload **before / after** photos.
3. Move **Claim Status** — pick **Counter Proposal** (proposal reveals) or **Rejected** (reason reveals);
   set a recovery amount; attach a warranty card; save.
4. Index shows open claims + pending recovery; **Purchase Return Report** exports the CSV.

## Related modules impacted

- **Reused (no changes):** OutsideLabourBill (41, item-wise ref), VendorMaster (vendor + transport),
  RegularSalesInvoice, WorkshopDepartment, EmployeeMaster (advisor / technician / store), Customer +
  Vehicle, SpareMaster + brand/uom/hsn/tax masters, PriorityMaster, FollowUpModeMaster.
- New permissions synced; menu under **Inventory**.

## Effect on the system

Provides the parts-to-vendor return / warranty note with a "Purchase Return Report". Additive — no
existing module changed.

## Design note (interpretation — flag)

**This module is functionally near-identical to module 43 (Outside Labour Return / Warranty)** — the
client's spec for #51 repeats #43's field list verbatim, the only real differences being the name and the
"Purchase Return Report" vs "Outside Labour Return Register". Per the numbered spec I built it as its own
module (own tables/route/report). If #51 and #43 are meant to be **one** module (parts-return being just
another lens on the same warranty workflow), we can retire one and point both menu entries at it — say
the word and I'll consolidate.
