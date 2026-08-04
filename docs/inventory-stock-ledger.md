# Stock ledger: FIFO layers, batch/expiry, negative-stock guard

Closes the gaps found when auditing how inventory was consumed after the 29,681-spare import: the ledger
declared 11 movement types but only 5 were ever written, the main workshop path (IPO) never deducted, stock
counts recorded variance without posting it, `fifoLayers()` had no callers, and there was no expiry concept
anywhere.

Rather than bolt on four separate features, they all come out of **one mechanism**.

## The idea

`stock_entries` was already an append-only signed ledger. It is now a **layered** one:

- An **IN** entry (purchase, opening, sale return, IPO return, positive adjustment) **is a cost layer** — it
  carries the rate the goods arrived at, plus an optional `batch_no` / `expiry_date`.
- An **OUT** entry points at the layer it drew from via **`layer_id`**.

One issue can produce several OUT entries — one per layer it consumes — each at that layer's true rate. From
that single change you get:

| Ask | How it falls out |
|---|---|
| FIFO costing | the OUT entry carries the layer's rate, not a flat average |
| Negative-stock guard | allocation fails when the layers run out |
| Batch / expiry | the layer carries them; the OUT entry inherits them |
| Traceability | `layer_id` walks an issue back to the invoice it arrived on |

## New pieces

- **`StockIssuer`** (`app/Modules/Inventory/Services/StockIssuer.php`) — the single way stock moves:
  `receive()`, `issue()`, `adjust()`, `reverse()`.
- **`StockLedger`** gained `openLayers()`, `lastInwardRate()`, `batchBalances()`, `expiringBatches()`.
- **`InsufficientStockException`** — carries the spare, requested and available quantities.
- **`MovesStock`** (`app/Concerns/MovesStock.php`) — turns that exception into a validation error on the
  offending line instead of a 500, and restores `editingId` after the rolled-back save.
- **`config/inventory.php`** — `block_negative_stock` (default **true**), `expiry_warning_days` (default 90).

### Schema

| Migration | Change |
|---|---|
| `2026_08_04_120000` | `stock_entries.batch_no`, `.expiry_date`, `.layer_id` (self FK); `spares.tracks_batch` |
| `2026_08_04_120100` | `purchase_entry_items.batch_no`, `.expiry_date` |

## Ordering rule

Layers are consumed **soonest-expiry first, then oldest receipt**. A batch received earlier but expiring later
does not go out ahead of one that lapses next week — which is the whole point of tracking expiry.

## What now moves stock

| Module | Type | Sign | Status |
|---|---|---|---|
| PurchaseEntry | `purchase` | + | retrofitted — now creates a layer with batch/expiry |
| SalesReturn | `sale_return` | + | retrofitted — re-enters at last inward cost, not sale price |
| CounterSalesInvoice | `sale` | − | retrofitted — FIFO-costed + guarded |
| GoodsReturn | `purchase_return` | − | retrofitted — draws down the layer it came in on |
| Consumable | `consumption` | − | retrofitted — FIFO-costed + guarded |
| **InternalPartOrder** | `ipo_issue` / `ipo_return` | −/+ | **new** |
| **StockCounting** | `adjustment` | ± | **new** |
| **`import:opening-stock`** | `opening` | + | **new** |

**ChallanEntry deliberately posts nothing.** It is an *inbound* pre-invoice document (vendor → us) and
`purchase_entries.challan_id` links a purchase back to it — posting on both would double-count. The
`challan_out` / `challan_return` types in the enum were written for an outbound flow this system does not
have. If goods should land in stock at challan rather than at invoice, PurchaseEntry must then skip posting
when `challan_id` is set; that is a business decision, not a bug fix.

## Behaviour worth knowing

- **IPO**: only issues once past draft; cancelling returns the parts. Editing an issued order corrects the
  ledger rather than stacking onto it (reverse-then-repost, the pattern every module uses). A part coming back
  re-enters at the weighted-average rate it left at, so an unused part cannot revalue stock.
- **StockCounting**: posts only at `completed`, and is **always allowed to go negative** — the shelf is the
  authority, not the ledger. Cancelling a completed count reverses its adjustment.
- **Guard**: over-issuing rolls the whole document back and shows the message on the offending line
  (`OIL FILTER: asked for 10 but only 3 in stock.`). Set `INVENTORY_BLOCK_NEGATIVE_STOCK=false` for workshops
  that fit parts first and reconcile later — the shortfall is then recorded against no layer at the last
  inward rate, so the balance and cost stay honest.

## Opening stock

```bash
php artisan import:opening-stock storage/app/opening.csv --dry-run   # check first
php artisan import:opening-stock storage/app/opening.csv
```

CSV header: `part_no, qty, rate, batch_no, expiry_date, location` — only `part_no` and `qty` are required.
`part_no` matches `spares.spare_code` (case-insensitive). Each row becomes one `opening` layer. Idempotent per
spare (a spare that already has an opening entry is skipped); `--replace` recounts.

## How to visually test on the UI

1. **Inventory → Spares** → open an oil/chemical → tick **Track batch & expiry**, save.
2. **Purchase Entry** → new → add that spare as a line. **Batch No.** and **Expiry Date** appear only on that
   line. Set expiry ~20 days out, qty 8, rate 500, save.
3. **Stock Report** → an amber banner: *1 batch expiring within 90 days* — expand for batch, days left and
   value at risk. Backdate the expiry and it turns red with *already lapsed*.
4. **Internal Part Order** → issue 4 of that spare → stock drops to 4. Ask for 100 → the save is rejected on
   that line and nothing is written.
5. **Stock Counting** → count the spare at 2 against a system 4, set **Completed**, save → stock is 2 and an
   `adjustment` entry of −2 exists.

## Tests

| File | Tests |
|---|---|
| `StockIssuerTest.php` (new) | 13 — FIFO order, per-layer rates, guard, allow-negative, expiry-first ordering, layer tracing, batch balances, drained layers, expiry alerts, adjustments, reversal, zero-qty |
| `OpeningStockImportTest.php` (new) | 8 — layers, batch/expiry, case-insensitive part no, idempotency, `--replace`, `--dry-run`, zero qty, missing file |
| `InternalPartOrderTest.php` | +5 — deduct + re-sync, guard rejection, return at issue rate, draft holds nothing, cancel restores |
| `StockCountingTest.php` | +4 — posts at completed only, tops up, allows negative, cancel reverses |
| `CounterSalesInvoiceTest.php` | +2 — over-sell blocked, FIFO cost across layers |
| `StockReportTest.php` | +3 — expiry banner, lapsed callout, quiet when clear |

**162 green** across every inventory-touching suite.

## Still open

- `stock_entries` is empty in dev — nothing has an on-hand balance until `import:opening-stock` runs with real
  numbers. Every spare reads as out-of-stock until then.
- GoodsHandover and GoodsReceipt still post nothing.
- `alertStatus()` keeps its `negative` state: reachable via count adjustments and via the allow-negative
  escape, so the stock report still needs to show it.
