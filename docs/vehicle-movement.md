# Vehicle Inward / Outward — module 68

A **single** module for both directions of the gate log — a vehicle entering (inward) or leaving
(outward) the premises. Records the parking slot, gate, outward purpose, driver, the security guard who
cleared exit, entry/exit timestamps (**TAT is derived**), the number-plate reading and captured images.
Header + image attachments.

## What's done

`app/Modules/VehicleMovement/` (`IO-#####`, group **Workshop**, route `vehicle-movement.index`)
- Parent `vehicle_movements` + `vehicle_movement_attachments`.
- Enums: `movementTypes` (inward / outward), `parkingSlots` (Slot 1–3 / Outside gate), `gates`
  (Gate 1 / 2), `outwardTypes` (trial run / outside labour / final delivery / fuel filling / PUC /
  RTO passing), `driverTypes` (customer self / representative / workshop staff / vendor / towing),
  `jobStatuses` (pending / completed / cancelled). Attachment types: entry / exit / number-plate photo.
- Links customer vehicle, job card + **Gate Pass approval (66)** (both relevant to outward), the
  delivered-by employee and the exit-clearing security guard.
- **TAT is computed** from entry → exit (`tatMinutes()` / `tatLabel()` → e.g. "2h 30m"); null while the
  vehicle is still on premises.
- **Reveal / conditional:** the outward purpose, gate-pass, job-card and driver-type fields show only
  for an outward movement and the purpose is required; picking a vehicle prefills the number plate;
  `exit_at` must be ≥ `entry_at`.
- **Index**: KPIs (Vehicles Inward Today / Trial Runs Today / Vehicles Outward Today), CSV **Inward /
  Outward Register** (with TAT column).
- Tests: **10 green**.

## How to visually test on the UI

1. **Workshop → Vehicle Inward / Outward → Log Movement.** Leave **Movement Type = Inward**, pick a
   vehicle (number plate prefills), a slot and gate, set the entry time; save.
2. Flip **Movement Type = Outward** — the **Outward Purpose** (required), **Gate Pass Ref**, **Job Card**
   and **Driver Type** appear. Set exit time; the register shows the derived **TAT**.
3. Add an entry / exit / number-plate photo. Index KPIs count today's inward / trial / outward; **Inward
   / Outward Register** exports the CSV.

## Related modules impacted

- **Reused (no changes):** CustomerVehicleMaster, JobCard, GatePassApproval (66), EmployeeMaster.
- New permissions synced; menu under **Workshop**.

## Effect on the system

Adds the security-gate movement log, tying outward deliveries to their gate-pass approval and giving
per-vehicle premises TAT. Additive — no existing module changed.

## Design note (interpretation — flag)

"Record actual vehicle exit via number plate reading, capture image" is modelled as a free-text
`number_plate` field (prefilled from the vehicle) + image attachments — actual ANPR / camera capture is
a device-integration follow-up, not wired here.
