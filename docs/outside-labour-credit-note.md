# Outside Labour Credit Note / Debit Note — module 44

A CN / DN raised against an outside-labour bill or warranty return to settle an adjustment (rework,
defect, billing correction) commercially. Header + item-wise labour lines (each pointing at the OL
bill) + document attachments.

## What's done

`app/Modules/OutsideLabourCreditNote/` (`OLCN-#####` / `OLDN-#####`, group **Workshop**, route
`outside-labour-credit-note.index`)
- Parent `outside_labour_credit_notes` + `outside_labour_credit_note_items` (labour line + OL-bill ref,
  uom / hsn / tax, qty, rate) + `outside_labour_credit_note_attachments`.
- **Doc-number prefix follows the note type:** `OLCN-` for a credit note, `OLDN-` for a debit note.
- Enums: `noteTypes` (credit_note / debit_note), `invoiceTypes` (e_credit_note / tax_credit_note /
  bill_of_supply / bill_book_memo), `returnReasons` (merged labour + parts list, incl. incorrect qty /
  rate / discount), `commercialSettlements` (partial / full), `warrantyTypes` (vendor / manufacturer),
  `warrantyPeriods` (3 / 6 / 12 / 24 months), `statuses` (posted / cancelled), `vendorRatingTypes`.
  Attachment types: vendor bill copy / warranty card.
- **Index**: KPIs (Credit Notes Posted / Debit Notes Posted / Cancelled), CSV **Outside Labour Return
  Register**.
- Tests: **9 green**.

## How to visually test on the UI

1. **Workshop → Outside Labour Credit / Debit Note → New Note.** Set **Note Type = Credit Note**, an
   invoice type + service category, pick the **Vendor** (required), and the **Warranty / Return** ref.
2. Add labour lines — each with its **OL Bill** ref, uom, tax, qty, rate.
3. Set **Return Reason**, **Commercial Settlement**, warranty type/period, note amount; attach a vendor
   bill copy; save. The number is `OLCN-…` (or `OLDN-…` for a debit note).
4. Index KPI cards split posted credit vs debit; **Return Register** exports the CSV.

## Related modules impacted

- **Reused (no changes):** OutsideLabourBill (41), OutsideLabourReturn (43), VendorMaster (vendor +
  transport), ServiceSpecialistMaster, JobCard, CustomerVehicleMaster, WorkshopDepartment,
  EmployeeMaster, uom / hsn / tax masters.
- New permissions synced; menu under **Workshop**.

## Effect on the system

Adds the commercial settlement step after an outside-labour warranty return (43) — the CN/DN that
actually moves money for the adjustment. Additive — no existing module changed.
