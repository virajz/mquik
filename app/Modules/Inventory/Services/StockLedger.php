<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\StockEntry;
use App\Modules\SpareMaster\Models\SpareMaster;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockLedger
{
    /**
     * Current on-hand qty for a spare (sum of all signed entries).
     */
    public static function currentQty(int $spareId): float
    {
        return (float) StockEntry::where('spare_id', $spareId)->sum('qty');
    }

    /**
     * Bulk current-qty map for a set of spare IDs.
     * Returns ['spare_id' => qty_float, ...]
     *
     * @param  int[]  $spareIds
     * @return array<int, float>
     */
    public static function currentQtyMap(array $spareIds): array
    {
        if (empty($spareIds)) {
            return [];
        }

        return StockEntry::query()
            ->select('spare_id', DB::raw('SUM(qty) as total_qty'))
            ->whereIn('spare_id', $spareIds)
            ->groupBy('spare_id')
            ->pluck('total_qty', 'spare_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * FIFO cost layers: only positive (IN) entries, oldest first, with running balance.
     * Used for COGS calculation and cost-of-issue.
     *
     * @return Collection<int, object{id: int, moved_at: Carbon, qty: float, rate_per_unit: float, entry_type: string}>
     */
    public static function fifoLayers(int $spareId): Collection
    {
        return StockEntry::where('spare_id', $spareId)
            ->where('qty', '>', 0)
            ->orderBy('moved_at')
            ->orderBy('id')
            ->get(['id', 'moved_at', 'qty', 'rate_per_unit', 'entry_type']);
    }

    /**
     * Layers with stock still on them, in the order an issue should consume
     * them: soonest expiry first (so batches are used before they lapse), then
     * oldest receipt. Layers already drawn down to zero are dropped.
     *
     * @return list<array{id: int, remaining: float, rate_per_unit: float, batch_no: ?string, expiry_date: ?string}>
     */
    public static function openLayers(int $spareId): array
    {
        $inward = StockEntry::query()
            ->where('spare_id', $spareId)
            ->where('qty', '>', 0)
            // NULLS LAST keeps undated stock behind anything that can expire.
            ->orderByRaw('expiry_date asc nulls last')
            ->orderBy('moved_at')
            ->orderBy('id')
            ->get(['id', 'qty', 'rate_per_unit', 'batch_no', 'expiry_date']);

        if ($inward->isEmpty()) {
            return [];
        }

        $consumed = StockEntry::query()
            ->whereIn('layer_id', $inward->pluck('id'))
            ->groupBy('layer_id')
            ->selectRaw('layer_id, sum(qty) as used')
            ->pluck('used', 'layer_id');

        $layers = [];
        foreach ($inward as $layer) {
            // Consumption is stored negative, so adding it draws the layer down.
            $remaining = (float) $layer->qty + (float) ($consumed[$layer->id] ?? 0);
            if ($remaining <= 0.0001) {
                continue;
            }
            $layers[] = [
                'id' => $layer->id,
                'remaining' => $remaining,
                'rate_per_unit' => (float) $layer->rate_per_unit,
                'batch_no' => $layer->batch_no,
                'expiry_date' => $layer->expiry_date,
            ];
        }

        return $layers;
    }

    /**
     * The rate the part most recently came in at — what an issue is valued at
     * when it runs past the last layer, and what a positive count adjustment
     * tops up at.
     */
    public static function lastInwardRate(int $spareId): float
    {
        return (float) (StockEntry::query()
            ->where('spare_id', $spareId)
            ->where('qty', '>', 0)
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->value('rate_per_unit') ?? 0.0);
    }

    /**
     * On-hand quantity per batch for one spare, newest expiry last.
     *
     * @return list<array{batch_no: ?string, expiry_date: ?string, qty: float, rate_per_unit: float}>
     */
    public static function batchBalances(int $spareId): array
    {
        return array_map(fn ($layer) => [
            'batch_no' => $layer['batch_no'],
            'expiry_date' => $layer['expiry_date'],
            'qty' => $layer['remaining'],
            'rate_per_unit' => $layer['rate_per_unit'],
        ], self::openLayers($spareId));
    }

    /**
     * Batches with stock still on them that lapse within `$days` — the stock
     * report's expiry alert. Already-lapsed batches are included (negative
     * `days_left`) because those are the urgent ones.
     *
     * @return Collection<int, object>
     */
    public static function expiringBatches(int $days): Collection
    {
        $cutoff = today()->addDays($days);

        return StockEntry::query()
            ->from('stock_entries as layer')
            ->join('spares', 'spares.id', '=', 'layer.spare_id')
            ->whereNotNull('layer.expiry_date')
            ->where('layer.qty', '>', 0)
            ->whereDate('layer.expiry_date', '<=', $cutoff)
            ->selectRaw('layer.id, layer.spare_id, spares.name as spare_name, layer.batch_no, layer.expiry_date, layer.rate_per_unit')
            ->selectRaw('layer.qty + coalesce((select sum(o.qty) from stock_entries o where o.layer_id = layer.id), 0) as remaining')
            ->havingRaw('layer.qty + coalesce((select sum(o.qty) from stock_entries o where o.layer_id = layer.id), 0) > 0')
            ->groupBy('layer.id', 'layer.spare_id', 'spares.name', 'layer.batch_no', 'layer.expiry_date', 'layer.rate_per_unit', 'layer.qty')
            ->orderBy('layer.expiry_date')
            ->get();
    }

    /**
     * Alert status for a spare given its current qty and master min/max.
     * Returns 'ok' | 'below_min' | 'above_max' | 'zero' | 'negative'
     */
    public static function alertStatus(float $currentQty, float $minQty, float $maxQty): string
    {
        if ($currentQty < 0) {
            return 'negative';
        }
        if ($currentQty === 0.0) {
            return 'zero';
        }
        if ($minQty > 0 && $currentQty < $minQty) {
            return 'below_min';
        }
        if ($maxQty > 0 && $currentQty > $maxQty) {
            return 'above_max';
        }

        return 'ok';
    }

    /**
     * Full stock snapshot for a single spare — used by the IPI response screen.
     *
     * @return array{current_qty: float, min_qty: float, max_qty: float, alert: string, location: ?string, avg_rate: float}
     */
    public static function snapshot(SpareMaster $spare): array
    {
        $currentQty = self::currentQty($spare->id);
        $avgRate = (float) StockEntry::where('spare_id', $spare->id)
            ->where('qty', '>', 0)
            ->average('rate_per_unit') ?? 0.0;

        return [
            'current_qty' => $currentQty,
            'min_qty' => (float) $spare->min_qty,
            'max_qty' => (float) $spare->max_qty,
            'alert' => self::alertStatus($currentQty, (float) $spare->min_qty, (float) $spare->max_qty),
            'location' => $spare->location,
            'avg_rate' => round($avgRate, 2),
        ];
    }
}
