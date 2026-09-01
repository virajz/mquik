The things I genuinely couldn't verify — client-side behaviour and visual layout — plus a few judgment calls. In priority order:

Can't-verify-from-here (JS/browser behaviour) — highest value:

Appointment → Customer Complaints checkboxes — pick MECHANICAL, click the PERIODIC MAINTENANCE group header: all children should tick instantly, count badge updates, un-tick one → header unchecks. This was the bug you caught; my fix is verified only at HTML level.
Pickup/Drop → Vehicle Condition photos — select View + When, then upload an image: the two selects must not reset (the reported override bug; same class of bug as #1).
Document Collection → Copy message — click it, paste somewhere, check the "Copied!" flash; then Send on WhatsApp on a customer with a phone — it should open WhatsApp with the doc list pre-filled.
Hard-refresh every tab first (⌘⇧R) — stale Livewire snapshots from before today's property changes will throw PropertyNotFound errors that look like bugs but aren't.
One full loop worth walking (10 min):

Appointment (both-legs type, tick services, save) → truck icon → the Pickup/Drop should arrive pre-filled with the right slot per leg, addresses, services, complaints. Then: assign driver → Departed → Send OTP → verify the code from the toast → status should read Vehicle Collected on its own → Record Inward for that vehicle → pickup job Completed, appointment Arrived.
GateInOut edit → fill the delivery set → Mark delivered → exit stamps now, status flips, and try clicking it with a field missing (should error inline, not stamp).
Layout / taste (quick scans):

The three reworked listing filter bars (Appointments, Pickup/Drop, Inward/Outward, Doc Collection) — search width, Filters popover, chips on Appointments.
Doc Collection listing — 12 columns now; check it scrolls horizontally acceptably on your screen or tell me what to drop.
Required asterisks — any field showing one that shouldn't (the CSS derives them from required), or doubles.
Judgment calls to veto:

Pickup/Drop purpose rule: "tick a service or type a complaint" instead of complaint-always-mandatory.
Gate photo + driver photo inputs use direct-camera capture on phones — check that's the behaviour you want at the gate.
If the checkboxes (#1) or photo selects (#2) still misbehave, that's the cue to set up Pest browser testing so I can click these myself going forward.

---

# Session: 01/09/2026 — Appointments, slot logic, inward linkage

Hard-refresh every tab first (⌘⇧R). Property changes today will throw
PropertyNotFound on stale Livewire snapshots — that is not a bug.

## 1. Appointment listing — highest value

- [ ] **Sticky columns.** Scroll the table sideways. **No.** must stay pinned
      left and **Actions** pinned right, with a shadow appearing on the frozen
      edge. Check both light and dark mode — the pinned cells carry their own
      background and will look wrong if it does not match your surface.
- [ ] **Columns picker** (button beside Filters). Untick a few → those columns
      vanish, header and cells together, no misalignment. Navigate away and back
      → the choice is remembered (session, not URL). "Show all" restores.
- [ ] **When vs Entry.** APT-00002 used to show `09:00 – 10:00` under "When" —
      that was its *pickup* window. It must now read its appointment time.
- [ ] **Row density.** One line per booking. Long pending reason on **APT-00009**
      must clip with an ellipsis, not stretch the table.
- [ ] **No. and Job Card and Pickup/Drop are links.** Job Card column only fills
      once you set the status filter to All — see caveat below.
- [ ] **Delete.** One shared modal now, not one per row. Confirm the heading
      names the right appointment when opened from different rows.

## 2. Appointment edit

- [ ] **Pending Reason** spans the full row.
- [ ] **Service Type / Advisor / Technician** populate as soon as a department is
      picked, and the stale **"No results found"** row must be gone. Try
      switching departments a few times.
- [ ] **Advisor list is department-scoped.** Pick SERVICE → 6 advisors. Pick
      TYRE → amber note "No staff are mapped…" and the full list. Only
      SERVICE / BODYSHOP / MECHANICAL have staff mapped today.
- [ ] **Timeline strip** under the slot pickers: collect → at workshop → return,
      in order. Set a pickup slot *after* the appointment time — that step turns
      amber with a triangle, and an amber warning appears under the picker
      itself (not in the callout at the top).
- [ ] **One time format.** Every time on the page is `h:i A`. No 24-hour windows
      anywhere — dropdowns, timeline, warnings, listing.
- [ ] **Linked Records panel** appears only when the booking has spawned
      something. Each row links out.

## 3. Inward (Gate In/Out) — new

- [ ] Pick a vehicle that has **one** open booking → **Against Appointment**
      fills in automatically, with the "only one open booking" note.
- [ ] Vehicle with **two** open bookings → stays blank, both listed, you choose.
- [ ] Vehicle with **none** → field disabled, "No open booking for this vehicle".
- [ ] Type a plate instead of picking → same behaviour.
- [ ] Save → that appointment flips to **Arrived**.

## 4. The "No results found" fix — spot-check elsewhere

Same bug, same fix, other screens:

- [ ] Any form with **State → City → Area** (shared partial, ~30 forms).
      Pick a state → the city list must not show "No results found" above it.
- [ ] Labour Master → **Sub-Group** after picking Inv. Group.
- [ ] Recommendation Description Master → **Sub category** after Category.

## Caveats — expected, not bugs

- **Job Card column reads `—` in the default view.** A booking with a job card
  derives to *completed*, which "Open (unfinished)" hides. Switch to All.
- **Nothing will show Arrived until new inwards are recorded.** Arrival is now a
  real FK (`gate_visits.appointment_id`); the backfill matched 0 of 7 existing
  visits because none carry a job card either. APT-00002 previously read Arrived
  off a gate entry 16 days after its appointment — that was the bug.
- **Slots fill faster.** Drop legs now consume slot capacity alongside pickups.
  If `max_vehicles_per_slot` was tuned against pickup-only counts, revisit it.
- **APT-00009 is now Pending** — I attached a deliberately long pending reason
  to it so the column could be tested. Clear `pending_reason_id` to undo.

## Correction — the real order is Inward, then Job Card

`job_cards.gate_event_id` is **required**: a card cannot be raised for a car
that has not arrived. So the chain is Appointment → Inward → Job Card, and
`gate_visits.job_card_id` is a legacy reverse pointer that nothing populates.

- [ ] **Job card off an inward.** Record an inward against a booking, then raise
      a job card from that inward — the booking, customer and vehicle should all
      carry over without being picked again.
- [ ] **Job card from a booking.** Use the "create job card" button on the
      appointment listing. If that booking has an inward, `gate_event_id` should
      already be filled. If the car has not arrived, the field stays empty and
      the card cannot be saved — that is correct, not a bug.

## Not done yet

- Sticky columns and the column picker exist only on Appointments. Job Card,
  Vehicle Inspection and Digital Inspection listings are equally wide.
- Dependent pickers not yet swept: DigitalInspection, JobCard, GateInOut,
  InternalPartOrder, PickupDrop, SpareMaster, DocumentCollection,
  InventorySearch.
