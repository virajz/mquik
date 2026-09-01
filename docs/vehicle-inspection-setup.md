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

---

# Vehicle Inspection — notes, headings, mandatory fields

| # | Requirement | Status |
| --- | --- | --- |
| 1 | Rename "Summary Notes" → "Internal Notes"; new Customer Notes that prints on the checklist | ✅ Done |
| 2 | Group names — bigger, in the logo orange | ✅ Done |
| 3 | Mandatory: Job Card, Template, Technician, floor in-charge **or** advisor, Action Type | ✅ Done |
| 4 | Shortcut button — Recommendation Desc | ✅ Done |

## What changed

**Two notes boxes, two audiences.** `summary_notes` is now labelled **Internal Notes** ("stays inside
the workshop — never printed"), and a new `customer_notes` column holds what prints on the
customer's copy. One box for both meant either the workshop censored itself or the customer read
something written for the bay. Both follow the app's capital-typing convention.

**Group headings** went from `text-xs … text-zinc-400` to `text-base font-bold` in the brand orange
(`--color-mq-orange-600`, lightened to `-400` in dark mode so it stays readable).

**Mandatory fields.** Job Card and Template were already required. Technician now is too. Floor
In-charge and Advisor are an either/or — the same `Rule::requiredIf` shape the job card uses for
technician-or-vendor — with one shared message ("Name a floor in-charge or an advisor") so it reads
as one rule rather than two failures. **Advisor is a new column**; the setup had no field for it.

Action Type carries its label and required marker on every checklist row. It stays answerable as
*Pending* while the sheet is in progress — making IA/FA/NA mandatory at save time would make the
In Progress status unreachable, since a sheet is only Completed once every row is answered. That
rule already enforces the intent: an inspection cannot finish with an unanswered checkpoint.

**Shortcut** — an open-in-new-tab button beside Quick add on every checklist row, going to
Recommendation Descriptions. Same pattern as the master shortcuts on appointments.

## Knock-on

`DigitalInspectionFactory` now supplies a technician and a floor in-charge, so a factory-made sheet
can be reopened and saved. Five existing tests were updated to fill the newly mandatory fields.

## How to check on the UI

1. **Digital Inspections → New** — Save with nothing filled: Job Card, Template and Technician all
   complain. Add those three and save again: it asks for a floor in-charge or an advisor. Fill
   either one — it saves.
2. Group headings on the checklist are noticeably larger and orange.
3. On a checklist row, the arrow button opens Recommendation Descriptions in a new tab.
4. At the foot: **Internal Notes** and **Customer Notes** are separate boxes.

---

# Vehicle Inspection — listing and reports

| # | Requirement | Status |
| --- | --- | --- |
| 1 | Default to Pending inspections on load | ✅ Done |
| 2 | Easy search (% or space) by vehicle + reg. no | ✅ Done |
| 3 | New filters: From/To date, Department, Service Type, Advisor, Floor In-charge | ✅ Done |
| 4 | New columns: Inspection date & time, Work start, Work complete, TAT, Vehicle name, Department, Service Type, Advisor, Floor In-charge, Bay No. | ✅ Done |
| 5 | Report — date-wise & technician-wise TAT, date-wise Future Jobs (FA) not approved | ✅ Done |
| 6 | Sort on every column heading | ✅ Done |
| 7 | ID format `MQ/VI/26-27/00001` | ✅ Done |

## What changed

**Search was broken here too.** `$searchableFields` carried a bare `registration_no`, which is not a
column on `digital_inspections` — every search threw. It now reaches the plate through the job card,
with VIN, model, brand and customer name alongside. `GJ 05 XY 7777`, `GJ05XY7777` and `GJ05%7777`
all find the same sheet.

**Pending by default.** `pendingStatuses()` is Pending + In Progress — the day's work, not the
archive. The status select opens on it, and the chip row says so.

**Filters** follow the pickup/drop pattern: search takes the width, status inline, everything else
behind **Filters** with removable chips. Department and Service Type filter through the job card,
since the sheet does not carry them. The date range picks its own target column — inspection /
work start / work complete — whitelisted in `dateColumn()`.

**Columns** — No., Inspected, Job Card, Vehicle (reg + model), Department, Service Type, Advisor,
Floor In-charge, Technician, Bay, Template, Work Start, Work Complete, TAT, Items, Status. Seventeen
in all, scrolling horizontally inside their own container. All sortable; related names use a
correlated subquery, because ordering by a foreign key sorts by row id and reads as random.

**Bay is a new column on the sheet.** Nothing linked an inspection to a bay, so the listing had
nowhere to read Bay No. from. There is now a Bay picker in Inspection Setup, beside Advisor.

**Numbering** — `MQ/VI/26-27/00001`, per financial year, continuing from the highest issued rather
than a count (which collides the moment the run has a gap). Portable `substr` over the known prefix,
not `split_part`, so the SQLite test suite runs.

## Inspection Reports — `/inspection-report`

Menu: **Inspection → Inspection Reports**. Two tabs over one set of filters (technician, department,
service type, date range), each exportable as CSV.

**Technician TAT** — one row per day × technician: sheets, average, fastest, slowest, total. Only
finished sheets count; an unstarted or unfinished one has no turnaround to compare. Sortable on
every heading.

**Future Jobs (FA)** — every checkpoint marked **FA** with a repair, replacement or skimming
recommended, on a sheet the customer has **not** approved: deferred, declined, or never asked.
Approved work drops off the list because it has already been sold; an IA checkpoint is not a future
job; and an FA marked *Monitor* has nothing to sell. Newest first — the freshest are the ones still
worth a phone call. Columns: date, inspection no, vehicle, checkpoint, recommendation, severity,
technician, customer decision.

## How to check on the UI

1. **Inspection → Digital Inspections** — the page opens on Pending; switch to All to see the rest.
2. Search a plate with spaces, then the same plate typed without them.
3. **Filters** → Department narrows Service Type. Set a range and switch the target between
   inspection / work start / work complete.
4. Click each of the seventeen headings twice — sorts, then reverses.
5. **Inspection Reports** → Technician TAT compares days and people; Future Jobs lists the follow-up
   work. Export either as CSV.
