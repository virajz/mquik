# Insurance Policy Renewal Follow-Ups — module 77

Track follow-ups for upcoming insurance policy renewals: the expiring policy, reminder schedule,
follow-up attempts and customer response, escalation, retention and the → renewed / overdue / lost
lifecycle. **Also covers module 78 (Insurance Policy Renewal)** — the renewed-policy details and
attachments live on this record. Header + document attachments.

## What's done

`app/Modules/PolicyRenewalFollowUp/` (`IPR-#####`, group **Insurance**, route
`policy-renewal-follow-up.index`)
- Parent `policy_renewal_follow_ups` + `policy_renewal_follow_up_attachments`.
- **Policy details inline** (no separate insurance-policy master exists): insurance company, **policy
  type (reuses the InsurancePolicyType master)**, policy number, start / end dates. Plus customer +
  vehicle, assigned-by / to employees, and a renewal premium.
- Enums: `statuses` (pending / assigned / quotation_sent / renewed / overdue / lost_opportunity /
  cancelled), `priorities`, `reminderFrequencies` (before 15 / 7 days / today / after 1 week),
  `followUpAttempts`, `followUpModes`, `customerResponses` (the 12-value list), `lostReasons`,
  `escalations`, `escalationReasons`, `retentions` (active / lost / recovered). Attachment types: RC /
  Aadhar / PAN / previous policy / insurance quote / renewed policy / payment receipt.
- **Reveal / conditional:** lost reason required on *Lost Opportunity*; escalation reason required once
  an escalation is set.
- **Timestamps** stamped automatically: `response_at` on first customer response; `quote_shared_at` on
  *Quotation Sent*; `policy_issued_at` + `payment_received_at` on *Renewed*.
- **Index**: KPIs (**Policies Expiring This Week** / **Overdue Renewals** (red) / Today's Follow-Ups /
  Lost Renewals / **Renewal Revenue**), overdue expiry dates highlighted red; CSV **Ins. Policy Renewal
  Due Follow-Up Report**.
- Tests: **10 green**.

## How to visually test on the UI

1. **Insurance → Policy Renewal Follow-Ups → New Follow-Up.** Pick the **Customer** / **Vehicle**, the
   **Insurance Company** / **Policy Type**, policy number, start / end dates and premium.
2. In **Follow-up** set assignment, reminder frequency, attempt / mode / **Customer Response** (stamps
   response-at); move **Status** to *Quotation Sent* (stamps quote-shared) → *Renewed* (stamps
   policy-issued + payment-received) or *Lost Opportunity* (lost reason required).
3. Attach RC / previous or renewed policy / payment receipt; save. The index shows expiring-this-week,
   overdue (red), today's follow-ups, lost and renewal revenue; the report exports the CSV.

## Related modules impacted

- **Reused (no changes):** InsuranceCompanyMaster, InsurancePolicyTypeMaster, Customer + Vehicle,
  EmployeeMaster.
- New permissions synced; menu under **Insurance**.

## Effect on the system

Adds the insurance-renewal CRM pipeline — from expiry reminder through renewal or loss — with retention
and revenue tracking. Additive — no existing module changed.

## Design note (interpretation — flag)

Module **78 (Insurance Policy Renewal)** was **not built** — the spec marks it "covered in above"; the
renewed-policy details (dates, premium, renewed-policy attachment) live on this record. There is no
`insurance_policies` master table, so the policy is captured inline (number + dates + company + type);
if a policy master gets built later, these can become an FK. The spec's automation rules — auto-identify
expiring policies, generate follow-up records from active policies, auto-WhatsApp/Email reminders,
auto-assign to CRM, auto-escalate after N attempts, auto-close on renewal, auto-move to Overdue on
expiry, duplicate-prevention per policy, and live conversion-ratio updates — are **not wired** (they need
a scheduled job + the policy master + notifications, per the no-send policy). The Overdue status can be
set manually and overdue expiry is surfaced (red) on the index regardless of status.
