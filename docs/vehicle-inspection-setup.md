# Vehicle Inspection — New Entry (Inspection Setup)

| # | Requirement | Status |
| --- | --- | --- |
| 1 | Job Card — show model only, standard spaced reg. no., pending job cards only | ✅ Done |
| 2 | Status — belongs to history, auto-updates; Approved & Rejected not applicable | ✅ Done |
| 3 | Technician — only technicians in the dropdown | ✅ Done |
| 4 | Floor In-charge — only floor in-charges | ✅ Done |
| 5 | New read-only fields: Advisor, Dept, Service Type, Variant, Year, Odometer — from the job card | ✅ Done |

## What changed

**Job Card picker** now lists only `JobCard::pendingStatuses()` — inspecting a closed or cancelled
card is never what was meant. Each option reads `MQ/JC/26-27/1330 — SPARK · GJ 05 RH 3445`: the
model alone (the brand repeats down every row and crowds out what tells them apart), and the plate
through `RegistrationNumber::format()`.

**Status is derived** — `app/Modules/DigitalInspection/Support/InspectionStatus.php`, the same
doctrine as `AppointmentStatus`, `PickupDropStatus` and `InspectionOrderStatus`. Read off the
checklist itself, so the status can never disagree with the sheet it describes:

| Rung | Read off |
| --- | --- |
| Cancelled | deliberate act, sticks |
| Completed | every item answered |
| In Progress | some answered, some pending |
| Pending | nothing answered, or no checklist yet |

`refresh()` stamps `started_at` on the first answer and `completed_at` on the last, and clears
`completed_at` if an item is reopened — a completion stamp must not outlive the completion.
The form shows a read-only badge with one line of plain English; the select is gone.

**Approved / Rejected** left `statuses()`. The decision belongs to the estimate, not the inspection
sheet. The constants stay, and `allStatuses()` renders any historic row that still carries one.

**Technician and Floor In-charge** filter by designation rather than offering all staff — 10 and 2
respectively out of 64 active employees. Matched on the designation name (`TECHNICIAN`, `FLOOR`),
since the master carries no flag for either.

**Job card context** is a read-only panel under the picker: Advisor, Department, Service Type,
Variant, Year, Odometer. Read-only on purpose — an inspection that disagreed with its own job card
would be worse than useless.

## Data gaps you will see

The context panel is only as good as the masters behind it:

- **3,623 of 19,070 job cards have no service type** — from before it became mandatory. Those cards
  will show `—` for Service Type.
- **10,134 of 10,144 vehicles have no year of manufacture**, and 10,131 have no odometer reading.
  Both fields will read `—` on almost every car until the vehicle master is filled in.

Nothing to fix in code; the fields are wired and will populate as the masters do.

## How to check on the UI

1. **Inspection → Digital Inspections → New** — open the Job Card dropdown: only pending cards,
   each showing model and a spaced plate.
2. Pick one — Advisor, Dept, Service Type, Variant, Year and Odometer fill in below it.
3. Status is a badge, not a dropdown, and says why it reads what it reads.
4. Technician and Floor In-charge lists are short and role-appropriate.
5. Answer one checklist item and save — status moves to In Progress. Answer the rest — Completed.

---

# Vehicle Inspection — checklist vocabulary and TAT

| # | Requirement | Status |
| --- | --- | --- |
| 1 | Capture technician-wise TAT | ✅ Done |
| 2 | Check Type (Yes/No, Visual, Measurement…) — not applicable | ✅ Done |
| 3 | Action Type — only IA / FA / NA, keeping Pending | ✅ Done |
| 4 | Recommendation — add Skimming, drop No Action Required & Urgent Attention; disabled on NA | ✅ Done |
| 5 | Severity — High @ IA, Low @ FA, user can change; disabled on NA | ✅ Done |

## What changed

**Action Type** replaces the fifteen-value condition list with four:
`Pending`, `IA — Immediate Attention`, `FA — Future Attention`, `NA — No Attention`.
The old words (OK / Good / Excellent / Average / Poor / Critical / Not OK / Faulty / Adjust /
Repair-Replace / Not Checked / Not Applicable) said how a part *looked* without saying what to do
about it, which is the only part the advisor and the customer act on. They live on in
`allOutcomes()` so a historic row still renders its own wording, and validation accepts them so an
old sheet can be reopened and saved.

**Check Type** is off the row. It describes how a checkpoint is examined, not what was found.

**Recommendation** is now `Repair`, `Replace`, `Skimming`, `Monitor`. "No Action Required" and
"Urgent Attention" both restated the Action Type rather than naming a job.

**Severity** arrives filled in — High on IA, Low on FA — and the technician can move it. A
deliberate choice sticks: picking Critical on an IA row survives a later action-type change,
because the auto-fill only overwrites a blank or a value it set itself.

**No Attention** disables both Recommendation and Severity, and clears them. Enforced twice — in the
form and again in `syncItems()` — so a stale value cannot ride in on a resubmit.

**Technician TAT** is captured from the checklist stamps, which `InspectionStatus` sets as items are
answered, so the number can never drift from the sheet it measures:
- The edit page shows Technician / Started / Completed / TAT, with a running figure while open.
- The listing gains sortable **Started**, **Completed** and **TAT** columns, alongside the
  technician filter that was already there.

## Note: Final Inspection inherits this

`FinalInspection::results()` and `itemRecommendations()` deliberately delegate to the Digital
Inspection sets, so the final-inspection sheet now uses the same IA / FA / NA vocabulary. Its item
tables are empty, so nothing was lost, and its validation accepts the retired values via
`allResults()` / `allItemRecommendations()`. Say the word if the two sheets should have separate
vocabularies instead.

## How to check on the UI

1. **Inspection → Digital Inspections → New** — pick a job card and a template.
2. On a checklist row, set Action Type to **IA**: Severity fills in as High. Switch to **FA**: Low.
3. Change Severity to Critical, then switch Action Type — your choice stays.
4. Set **NA**: Recommendation and Severity both grey out and empty.
5. Save, then reopen — the header shows Technician, Started, Completed and TAT.
6. **Digital Inspections** listing — sort by TAT, filter by technician.

---

# Vehicle Inspection — recommendations, ordering and sign-off

| # | Requirement | Status |
| --- | --- | --- |
| 1a | Rename "Observation" → "Recommendation Desc" | ✅ Done |
| 1b | Recommendation Desc filtered by Category / Sub Category from a master | ✅ Done |
| 1c | Select multiple — Select All or one by one | ✅ Done |
| 1d | Quick Add with category & sub category | ✅ Done |
| 2 | Rename "Notes / Measurement" → "Notes / Observation" | ✅ Done |
| 3 | Inspection Group & Item ordering as a user preference | ✅ Done |
| 4 | Customer Explanation & Approval section | ✅ Done |
| 5 | Internal Control sign-off section | ✅ Done |

## Two new masters

**Inspection → Recommendation Categories** (`/recommendation-category-master`)
One self-referencing table, the way `inventory_groups` already does it here: no parent means a
Category, a parent means a Sub Category of it. Two tables would have duplicated every field and
every screen to say the same thing. Nesting is one level deep — a sub category cannot itself be a
parent, and deleting a category takes its sub categories with it.

**Inspection → Recommendation Descriptions** (`/recommendation-description-master`)
The wording a technician picks. Filed under a Category and optionally a Sub Category, which must
actually belong to that category — otherwise the filing lies. The same wording may live under two
different categories, but not twice inside one.

Both carry a `sequence_no`, so the picker order is a preference rather than an accident.

## On the inspection sheet

The free-text **Observation** box is gone. It is now **Recommendation Desc**, picked from the master
rather than typed — the `standard_observations` datalist behind it went with it. Each checklist row
gains a Category / Sub Category pair and a tick list of descriptions:

- **Select all** ticks everything currently on offer; **Clear** empties the row.
- **Quick add** creates wording in the master, with its category and sub category, and ticks it on
  the row in one step — no leaving the inspection to go fix a master.
- A description already ticked stays on offer even when the category narrows past it, or the row
  would render blank for a value it actually holds.
- **No Attention** clears the picks along with the recommendation and severity, enforced in the form
  and again on save.

Several descriptions apply to one checkpoint — "replace pads" and "skim discs" is one job to a
technician — so they live in `digital_inspection_item_recommendations` rather than a single column.

## Ordering

`inspection_item_groups` and `inspection_items` both gained `sequence_no`, editable as **Order** in
their masters and sortable in both listings. The checklist is seeded in group order, then item
order, so the sheet is walked the way the car is — bonnet, then wheels, then interior — instead of
alphabetically. Existing rows were seeded from their current alphabetical position, so nothing moved
before anyone set a preference.

## Customer Explanation & Approval

Three ticks (explained on lift / photos shared on WhatsApp / questions answered) and one decision —
Approved, Deferred or Declined — stamped with the moment it was recorded.

## Internal Control

Three sign-off rows: Technician, Floor Supervisor, Advisor. Each is a person and a moment. Naming
someone stamps the time; clearing the name removes it; re-saving does not move a stamp that already
exists — a signature records when somebody put their name to it, not when the form was last saved.
Each row's picker is filtered to the right designation.

## How to check on the UI

1. **Inspection → Recommendation Categories** — create BRAKES, then FRONT with BRAKES as parent.
2. **Inspection → Recommendation Descriptions** — add wording under BRAKES / FRONT.
3. **Digital Inspections → New** — a checkpoint now shows **Recommendation Desc**, not a free-text
   Observation box. Set Category to BRAKES: the list narrows.
   **Select all**, then **Clear**, then **Quick add** a new phrase — it appears ticked.
4. Set the row to **NA** — the picks, recommendation and severity all clear.
5. **Inspection Item Groups** — change an Order value and reload a new inspection: the groups move.
6. At the foot of the sheet, tick the customer section and name a technician in Internal Control.
   Save, reopen, save again — the sign-off time must not have moved.
