# Outside Labour Return / Warranty Claim — module 43

A **single** module covering both **parts and labour** returns / warranty claims to an outside vendor:
the service advisor asks the vendor to resolve a job under warranty (rework / replacement) or settle
the amount, and the vendor accepts, rejects or counter-proposes. Header (claim) + item-wise return
lines (spare **or** labour, each with before/after photo evidence + OL-bill ref) + document/evidence
attachments.

## What's done

`app/Modules/OutsideLabourReturn/` (`OLRR-#####`, group **Workshop**, route `outside-labour-return.index`)
- Parent `outside_labour_returns` + `outside_labour_return_items` (per line: `item_type` spare/labour,
  spare + brand/uom/hsn/tax, material condition, **before/after photo** paths, OL-bill ref) +
  `outside_labour_return_attachments`.
- **One entry handles parts + labour** — each line carries its own `item_type`.
- Enums: `returnTypes` (regular / warranty), `claimTypes` (outside_labour_warranty / parts_warranty),
  `returnReasons` (the full labour-side **and** parts-side reason list merged), `warrantyTypes`,
  `warrantyPeriods` (1/3/6/12 months), `reworkTypes` (refit / repair_again / repaint), `tatOptions`
  (1/2/3 days / custom), the 12-state `statuses` (requested → under_review → partially/fully_accepted →
  rework/replacement_in_progress → counter_proposal → cn/dn/replacement_adjusted → rejected /
  cancelled), `counterProposals`, `rejectionReasons`, `reminderFrequencies`, `vendorRatingTypes`.
  Item enums: `itemTypes`, `materialConditions` (new / used / unused / open_box). Attachment types:
  vendor bill copy / warranty card / complaint / damage photo / work order copy / inspection report +
  the additional views (front / rear / left / right / fault evidence).
- **Reveal / conditional:** counter proposal required on `counter_proposal`, rejection reason on
  `rejected`, custom TAT on `custom`, custom reminder days on `custom`. Spare pick auto-fills the line.
  Reminders are **config-only** (never sent).
- **Index**: KPIs (Open Warranty Claims / Recovery Amount Pending), CSV **Outside Labour Return
  Register**.
- Tests: **11 green**.

## How to visually test on the UI

1. **Workshop → Outside Labour Return / Warranty → New Return / Claim.** Set **Return Type = Warranty**,
   a **claim type** and **reason**, pick the **Vendor** (required), sales invoice, customer + vehicle.
2. Add return lines — flip **Item Type** between **Spare** (pick a spare, auto-fills) and **Labour**;
   set qty/rate, **material condition**, the **OL Bill** ref, and upload **before / after** photos.
3. **Warranty & Resolution:** set warranty type/period, rework type, TAT (Custom reveals days), a
   recovery amount, and move **Claim Status** — pick **Counter Proposal** (proposal field reveals) or
   **Rejected** (reason reveals).
4. Attach a warranty card / inspection report; save. Index shows open claims + pending recovery;
   **Return Register** exports the CSV.

## Related modules impacted

- **Reused (no changes):** OutsideLabourBill (41, item-wise ref), VendorMaster (vendor + transport),
  RegularSalesInvoice, WorkshopDepartment, EmployeeMaster (advisor / technician / store), Customer +
  Vehicle, SpareMaster + brand/uom/hsn/tax masters, PriorityMaster, FollowUpModeMaster.
- New permissions synced; menu under **Workshop**.

## Effect on the system

Closes the outside-labour loop **inquiry (12) → order (29) → bill (41) → return/warranty (43)** with a
single parts-and-labour claim workflow, including rework / replacement / counter-proposal / credit-note
settlement and recovery tracking. Additive — no existing module changed.

## Design note (interpretation — flag)

Per the spec's "single module for Parts & Labour Return", parts and labour share one claim with a
per-line `item_type`. Item-wise **before/after** photo evidence is stored as two image columns on each
line; the **additional** photo-evidence set (front/rear/left/right/fault) lives in the header
attachments with dedicated types. If you'd rather each line hold the full multi-angle photo set, that
becomes a separate line-photos child table — say the word.
