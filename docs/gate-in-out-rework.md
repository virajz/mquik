# Inward / Outward (GateInOut) — rework tracker

Audited against the module as it stands — the inward-chain work from earlier
sessions already covers a good third of this list.

Legend: `[ ]` pending · `[x]` done · `[~]` resolved by design (see note)

## Already in place (verified, no work needed)
- [x] 1. Inward — Entry Date & Entry Time disabled (readonly, stamped on create)
- [x] 2. Registration Number — quick-add for a first-time vehicle (picker + walk-in modal creating customer + vehicle)
- [x] 6. Job Card field gone from the entry — advisor creates job cards from the visit ("New Job Card" on the record)
- [~] 7. Multiple exits (trial run, outside labour, fuel…) — the **Movements** ledger; the only exit on the entry page is the final delivery
- [x] Search by space/% incl. vehicle+reg — shared `Searchable` upgrade already covers this model
- [x] Inward Date From & To filter — exists (`entered_at` range)

## Chunk 1 — Pickers & defaults
- [ ] 3. Show vehicle name (brand/model) for the selected reg. no.
- [ ] 4. Entry Gate defaults to GATE NO. 1
- [ ] 5. Exit Gate defaults to GATE NO. 2
- [ ] 8. Delivered By — only advisors and cashiers
- [ ] 9. Rename "Exit Approved By" → "Exit By (Security Guard)", only security guards offered

## Chunk 2 — Delivery flow & status
- [ ] 10. Exit Date & Exit Time — disabled; stamped by a "Mark delivered" action
- [ ] 11. Status auto-derived (pending → completed on delivery; cancel stays deliberate)
- [ ] 12. Mandatory: entry set on create (date/time/reg/gate/source); exit set (gate, driver type, delivered by, exit by) enforced at delivery
- [ ] 13. Image capture option (`captured_image_path` column exists, no UI yet)

## Chunk 3 — History (listing)
- [ ] 14. Default to Pending (vehicles still un-delivered) on load
- [ ] 15. Outward Date From & To filter
- [ ] 16. New columns: Vehicle (brand/model), Job Card No., Delivered By, Driver Type, Exit By
- [ ] 17. Sort on all column headings

## Notes answered
- **GE-00001 — "GE?"** = **Gate Event**. Numbering is stamped per visit at creation; keeping the prefix (renaming would fork history).
- **Outward Type mandatory?** Resolved by the client's own note — "capture different dates & outward types" is exactly the Movements ledger (trial run / outside labour / fuel filling each with out/in times). The final delivery is the single true outward, per the earlier decision that it needs no type.
- **Duplicate module** — `VehicleMovement` ("Vehicle Inward / Outward") vs GateInOut: confirmed duplication, 1 row of data, but it carries `gate_pass_approval_id` + attachments that GateInOut lacks. Chunk 3 removes it from the menu/routes; table kept until the gate-pass link is ported.
