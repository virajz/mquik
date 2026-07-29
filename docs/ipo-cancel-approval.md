# IPO Cancel Request / Response — module 33

Store-side request to **cancel an Internal Part Order** (IPO) plus the reviewer's response — approve,
reject, hold for clarification, or reverse. Reshaped from module 24 (Job Card Cancel Approval): a
header + attachment children, with a rich enum set for the reasons/impacts around a cancellation.

## What's done

`app/Modules/IpoCancelApproval/` (`ICR-#####`, group **Inventory**, route `ipo-cancel-approval.index`)
- Parent `ipo_cancel_approvals` + child `ipo_cancel_approval_attachments`.
- Links the **Internal Part Order** being cancelled (its number column is `order_no`), plus job
  card / vendor / employee context.
- Enums: `statuses` (under_review / approved / rejected / cancelled / reversed / needs_clarification),
  `cancellationReasons`, `impactOptions` (JSON multi-select), `categories`, `issueStatuses`,
  `returnStatuses`, `returnTypes`, `rejectionReasons`, `approvalLevels`.
- **Index**: KPIs (Under Review / Approved / Rejected / Closed), search, filters, CSV **IPO Report**.
- **Edit**: attachment child sync; reveal logic for rejection reason etc.
- Tests: **13 green**.

## How to visually test on the UI

1. **Inventory → IPO Cancel Approval → New.** Pick the **Internal Part Order**, a **cancellation
   reason**, tick one or more **impact** options, set **category / return type**.
2. Move **status** through Under Review → Approved / Rejected (rejection reason reveals on Rejected).
3. Attach a supporting document; save. Row appears with a status badge.
4. Index → **IPO Report** exports the CSV; KPI cards filter the list on click.

## Related modules impacted

- **Reused (no changes):** InternalPartOrder (column `order_no`), JobCard, VendorMaster, EmployeeMaster,
  and the IPO cancellation/rejection/return reason masters.
- New permissions synced; menu under **Inventory**.

## Effect on the system

Gives internal part orders a governed cancellation path with an audit trail, mirroring the job-card
cancel flow. Additive — no existing module changed.
