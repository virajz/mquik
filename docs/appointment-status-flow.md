# Appointment Status — how it moves

Appointment status is **derived, never typed**. There is no Status dropdown on the form. The badge
reads whatever the vehicle has actually done, worked out by
`App\Modules\Appointment\Support\AppointmentStatus::derive()` and recomputed by save-hooks on
`JobCard`, `GateInOut` and `PickupDrop`.

So you don't change the status. You do the real thing, and the status follows.

## The ladder

Highest precedence first — **this is precedence, not a sequence**. A booking that reaches Completed
stays Completed even though the earlier rungs are still true, and skipping ahead is fine: raise a job
card on a fresh booking and it goes straight to Completed without ever showing Arrived.

| Status | What makes it true |
|---|---|
| **Cancelled** | `cancelled_at` is set — the one deliberate act left |
| **Completed** | a job card exists with this `appointment_id` |
| **Arrived** | a gate visit (inward) exists for the vehicle, dated on or after the booking |
| **Vehicle Collected** | a linked pickup/drop reached collected, delivered or completed |
| **No-show** | the slot time passed and none of the above happened |
| **Pending** | a Pending Reason is on file — that *is* what pending means now |
| **Rescheduled** | `rescheduled_from_at` is set and nothing has happened yet |
| **Confirmed** | booked, nothing has happened yet (the default) |

The drop leg — vehicle delivered, gate pass, outward — happens **after** the job card and does not
touch the appointment. Once a job card is raised the booking's job is done; the return trip lives in
the PickupDrop job's own lifecycle.

## How to walk each status on the UI

Start at **Appointments** (`/appointments`). A new booking saves as **Confirmed**.

### → Pending
1. Open the appointment (**Edit**).
2. **Booking** section → set **Pending Reason**.
3. Save. Badge reads **Pending**, and the reason shows in the Pending Reason column on the listing.

Clear the reason and save to drop back to whatever the car is actually doing.

### → Vehicle Collected
Only meaningful when the booking involves a pickup.

1. Appointments list → find the row → click the **truck** icon (*Create pickup / drop from this
   appointment*). The Pickup/Drop form opens prefilled with the customer, vehicle and schedule.
2. Save it.
3. In that Pickup/Drop record, set **Status → Vehicle Collected**, and add the driver's condition
   photos in the **Photos** section (pick a View, set the leg, attach the image).
4. Save. The appointment badge reads **Vehicle Collected**.

Setting *Driver Assigned* or *Driver on the Way* instead will show on the appointment as a live
refinement, but does not change the stored status — the car isn't collected yet.

### → Arrived
1. Go to **Inward / Outward** (`/gate-in-out`) → **Record Inward**.
2. Pick the **same vehicle** as the appointment. Fill the entry gate and whatever else the gate needs.
3. Save. Every open booking for that vehicle recomputes to **Arrived**.

The gate has no appointment field of its own — the vehicle and the booking's own age are what tie the
two together, so an inward raised *before* the booking was made does not count.

### → Completed
1. Appointments list → find the row → click the **clipboard** icon (*Create job card from this
   appointment*). The Job Card form opens prefilled with customer, vehicle, department, service type
   and advisor.
2. Save. The appointment badge reads **Completed**, and that is terminal.

You can also reach this from the Job Card form directly, as long as the job card carries the
`appointment_id` — that is what the link is read from.

### → No-show
No action. Let the appointment time pass with no pickup, no inward and no job card. The badge turns
**No-show** on its own. Setting a Pending Reason suppresses it — a booking you are knowingly holding
is not a no-show.

### → Cancelled
1. Open the appointment (**Edit**).
2. Header → **Cancel Booking**.
3. Pick a **Cancel Reason** in the modal → **Cancel Booking**.

Cancellation outranks everything, including Completed. The appointment stays on file; the header
swaps to a **Restore** button, which clears the flag and lets the status re-derive from wherever the
car actually is.

## Full end-to-end walk

To see every rung in order on one booking:

1. **New Appointment** with a pickup-and-drop option, dated in the future → **Confirmed**
2. Truck icon → save the Pickup/Drop → set it to **Vehicle Collected** → **Vehicle Collected**
3. **Record Inward** for that vehicle → **Arrived**
4. Clipboard icon → save the Job Card → **Completed**
5. (Optional) Edit → **Cancel Booking** → **Cancelled**; **Restore** → back to **Completed**

## Backfill

Statuses that were set by hand before this change were recomputed once. If data is ever imported or
edited straight in the database, recompute with:

```php
App\Modules\Appointment\Models\Appointment::all()
    ->each(fn ($a) => App\Modules\Appointment\Support\AppointmentStatus::refresh($a));
```
