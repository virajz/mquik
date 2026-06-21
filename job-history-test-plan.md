# Test Plan — Job History: Status Tree + Pending Reason (Phase A)

> **Scope:** Manually verify what Phase A shipped — the `JobStageMaster`, the Stage / Pending Reason / Expected-Completion fields on the Job Card, the history logging of stage & pending changes, and the **Status Tree** on the Job History page.
> **Base URL:** `http://mquik.test` · **Date:** 2026-06-03
> **Pre-req:** run `npm run dev` (or `npm run build`) so the Blade changes render; log in as an admin/super-admin.

---

## ⚠️ Scope note — "Job History" has two faces
This plan covers the **per-job lifecycle** (Status Tree + timeline) that Phase A built. The spec's *"list of jobs — Completed/Pending/Cancelled"* is currently served by the **Job Card index** (`/job-cards`) filters — test those too (§5). A *dedicated* Job History / WIP list and **Vehicle History** (all past visits of one vehicle) are **not yet built** — out of scope here, open product decision.

---

## 1. Master: Job Stages (`/job-stage-master`)

| # | Step | Expected |
|---|---|---|
| 1.1 | Open **Workshop → Job Stages** | List shows 11 seeded stages with **Track** (Regular/Insurance) + **Order** columns. |
| 1.2 | Sort by **Order**, then by **Track** | Sorting works; Insurance stages run Document Collection → Claim Intimation → Surveyor Inspection → … → Claim Settlement & Delivery. |
| 1.3 | Search "SURVEYOR" | Filters to the Surveyor Inspection row. |
| 1.4 | **+ New** → name "TEST STAGE", Track = Insurance, Order = 250, Save | Created; toast shown; appears in list. Name stored UPPERCASE. |
| 1.5 | Edit it → toggle **Inactive** → Save | Row shows Inactive badge. |
| 1.6 | Confirm it's gone from the Job Card **Stage** picker (see §3) | Inactive stages don't appear in the dropdown. |
| 1.7 | Delete the test stage | Deletes (no job cards reference it). |
| 1.8 | ⋮ menu → **Export** | CSV downloads with Track + Order columns. |

---

## 2. Job Card form — new fields (`/job-cards/create`)

| # | Step | Expected |
|---|---|---|
| 2.1 | Create a job card (customer + vehicle + dept + advisor) | In **Timing & Routing** you now see **Expected Completion Date/Time**, and a row with **Status · Stage · Pending Reason**. |
| 2.2 | Stage dropdown | Lists active stages, each labelled with its track, e.g. "DOCUMENT COLLECTION (Insurance)". Searchable + clearable. |
| 2.3 | Pending Reason dropdown | Lists active pending reasons (Spare Awaited, Estimate Pending, …). Clearable. |
| 2.4 | Set Stage = "DOCUMENT COLLECTION", Pending Reason = "INSURANCE APPROVAL PENDING", Expected Completion = a date/time, **Create** | Saves with no errors; redirects to the Job Card list. |
| 2.5 | Re-open that job card (Edit) | Stage, Pending Reason, and Expected Completion are pre-filled with what you saved. |

---

## 3. History logging + Status Tree (`/job-cards/{id}/history`)

| # | Step | Expected |
|---|---|---|
| 3.1 | On the job card from §2.4, open **History** (button on the edit page, or `/job-cards/{id}/history`) | Page loads. |
| 3.2 | Look at the top **Status Tree** | Shows the **Insurance** track stages in order (because the current stage is an insurance stage). "DOCUMENT COLLECTION" is highlighted as **current** (orange); later stages are dashed/pending. |
| 3.3 | Each stage box | Current = orange arrow; completed (earlier than current) = green check + entry timestamp; pending = grey clock + "—". |
| 3.4 | Scroll to the timeline below | There's a **"Stage Changed"** event ("Stage: DOCUMENT COLLECTION") and a **"Pending Reason Changed"** event, each with timestamp + your user name. |
| 3.5 | Edit the job card → move Stage to "SURVEYOR INSPECTION" → Save → re-open History | Status Tree now highlights Surveyor Inspection as current; Document Collection + Claim Intimation show as completed (green) with their entry timestamps; a new "Stage Changed" event is at the top of the timeline. |
| 3.6 | Edit → clear the Pending Reason → Save → History | A "Pending Reason Changed" → "Pending reason cleared" event is logged. |

---

## 4. Track switching (regular vs insurance)

| # | Step | Expected |
|---|---|---|
| 4.1 | Create a new job card, set Stage = a **Regular** stage (e.g. "REPAIR") | — |
| 4.2 | Open its History | Status Tree shows the **Regular** track (Estimate Approval → Repair → Billing → Delivery) — *not* the insurance stages. Repair = current. |
| 4.3 | A job card with **no** stage set | Status Tree defaults to the **Regular** track with nothing highlighted (or hidden if you prefer — note actual behaviour). |

---

## 5. "List of jobs by status" — existing Job Card index (`/job-cards`)

| # | Step | Expected |
|---|---|---|
| 5.1 | Open **Workshop → Job Cards** | Lists all job cards with status badges, advisor, dept, dates. |
| 5.2 | Filter by **Status** = Completed / Open / Cancelled; filter by date range | List narrows correctly — this is the "list of jobs Completed/Pending/Cancelled, date/time-wise" from the spec. |
| 5.3 | Decide | Is this index enough for "Job History list", or do you want a dedicated WIP/Status board? (Open decision — feeds the next plan.) |

---

## 6. Permissions / regression

| # | Step | Expected |
|---|---|---|
| 6.1 | As a user **without** `job_stage_master.view` | Job Stages menu item hidden; `/job-stage-master` returns 403. |
| 6.2 | As a user without `job_history.view` | `/job-cards/{id}/history` returns 403. |
| 6.3 | Existing job-card flows (complaints, inventory, photos, requested repairs) | Still work unchanged (regression). |

---

## Automated coverage (already green)
- `php artisan test --filter='JobStageMaster|JobCard|JobHistory'` → 75 passed.
- Full suite: 918 passed / 16 skipped / 0 failed.
- Covers: JobStage master CRUD + track/order, stage change → history event, pending reason → history event + persistence.

---

## After testing — feedback to capture
1. Is the **Status Tree** the right shape (horizontal chips), or do you want a vertical tree / different visual?
2. Should an empty-stage card hide the tree or show the regular track greyed out?
3. Do you want a **dedicated Job History / WIP list** screen (beyond the Job Card index)?
4. Greenlight **Phase B — Document Collection** (designed in `~/.claude/plans/typed-forging-planet.md`)?
