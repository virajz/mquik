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
