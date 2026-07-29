# Goods Handover / Technician Parts Return — module 46

The store hands received parts to a technician (against a GRN / FWO / job card) and captures any parts
the technician returns (excess, wrong, not required, defective). Header + item-wise lines (each with
material condition, physical-verification outcome, returned qty and a spare photo) + footer evidence
attachments.

## What's done

`app/Modules/GoodsHandover/` (`GHO-#####`, group **Inventory**, route `goods-handover.index`)
- Parent `goods_handovers` + `goods_handover_items` + `goods_handover_attachments`.
- **People:** handover-by (store executive), **received-by (technician, required)**, verified-by (floor
  in-charge / advisor), parts-return-by (technician / advisor), optional service contractor (vendor).
- **Sources:** Goods Receipt (45), FWO, job card.
- **Per line:** spare (auto-fills brand/uom/description), vehicle, **issued qty** + **returned qty**,
  `materialConditions`, `physicalVerifications` (excess / less / damage / wrong part / mfg defect /
  missing / expired / packaging), `damageTypes` (old damage / fitment damage), a spare photo.
- Enums: `materialReturnStatuses` (partial / full), `returnReasons` (excess issue / wrong part /
  not required / defective), `statuses` (received / verification_pending / verified / mismatch_accepted
  / mismatch_rejected). Footer attachment types: damage photo / fault evidence.
- **Reveal / conditional:** return reason required once a material-return status is set.
- **Index**: KPIs (Materials Issued Today / Material Returns / Technicians Served) **plus a
  technician-wise consumption panel** (issued vs returned qty per receiving technician), CSV
  **Purchase Report**.
- Tests: **10 green**.

## How to visually test on the UI

1. **Inventory → Goods Handover / Parts Return → New Handover.** Pick the **Received By (technician,
   required)**, handover-by/verified-by, and a **GRN / FWO / job card** reference.
2. Add parts — pick a spare (auto-fills), set **Issued Qty**; for a return set **Returned Qty**,
   material condition, physical verification and damage type; upload a photo.
3. Set a **Material Return Status** → the **Return Reason** appears (required); set the handover status.
4. Save. The index shows issued-today / returns / technicians-served KPIs, a **technician-wise
   consumption** breakdown, and the **Purchase Report** CSV export.

## Related modules impacted

- **Reused (no changes):** GoodsReceipt (45), FinalWorkOrder, JobCard, VendorMaster, SpareMaster +
  brand/uom masters, CustomerVehicleMaster, WorkshopDepartment, EmployeeMaster.
- New permissions synced; menu under **Inventory**.

## Effect on the system

Completes the parts flow into the workshop floor: **receive (45) → hand to technician / capture returns
(46)**, with per-technician consumption visibility. Additive — no existing module changed.
