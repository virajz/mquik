<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The single way stock moves.
 *
 * Receiving creates a FIFO cost layer. Issuing walks those layers oldest-first
 * (earliest expiry first for batch-tracked parts) and writes one OUT entry per
 * layer it draws from, each carrying that layer's rate and batch. That one
 * mechanism gives three things at once: true FIFO cost-of-issue, batch/expiry
 * traceability from receipt to job card, and a natural place to refuse an issue
 * that would take the ledger negative.
 *
 * Every module follows the same edit-safe pattern: `reverse()` the document's
 * previous entries, then post them again from the current lines.
 */
class StockIssuer
{
    /**
     * Record goods coming in — a new cost layer.
     *
     * @param  array{batch_no?: ?string, expiry_date?: ?string, location?: ?string, notes?: ?string, moved_at?: mixed}  $options
     */
    public static function receive(
        int $spareId,
        float $qty,
        float $ratePerUnit,
        string $entryType,
        ?Model $source = null,
        array $options = [],
    ): ?StockEntry {
        if ($qty <= 0) {
            return null;
        }

        return StockEntry::create([
            'spare_id' => $spareId,
            'entry_type' => $entryType,
            'source_type' => $source ? $source::class : null,
            'source_id' => $source?->getKey(),
            'qty' => $qty,
            'rate_per_unit' => $ratePerUnit,
            'batch_no' => self::normaliseBatch($options['batch_no'] ?? null),
            'expiry_date' => $options['expiry_date'] ?? null,
            'location' => $options['location'] ?? null,
            'moved_at' => $options['moved_at'] ?? now(),
            'actor_user_id' => auth()->id(),
            'notes' => $options['notes'] ?? null,
        ]);
    }

    /**
     * Issue stock, consuming FIFO layers.
     *
     * Returns one OUT entry per layer drawn from — a single issue of 10 against
     * layers of 4 and 6 produces two entries, so each carries the true rate it
     * left at.
     *
     * @param  array{allow_negative?: bool, location?: ?string, notes?: ?string, moved_at?: mixed}  $options
     * @return list<StockEntry>
     *
     * @throws InsufficientStockException when short and negative stock is blocked
     */
    public static function issue(
        int $spareId,
        float $qty,
        string $entryType,
        ?Model $source = null,
        array $options = [],
    ): array {
        if ($qty <= 0) {
            return [];
        }

        return DB::transaction(function () use ($spareId, $qty, $entryType, $source, $options) {
            $layers = StockLedger::openLayers($spareId);
            $available = array_sum(array_map(fn ($l) => $l['remaining'], $layers));

            $allowNegative = $options['allow_negative'] ?? ! config('inventory.block_negative_stock', true);
            if ($qty > $available + 0.0001 && ! $allowNegative) {
                throw new InsufficientStockException(
                    $spareId,
                    SpareMaster::whereKey($spareId)->value('name') ?? 'Spare #'.$spareId,
                    $qty,
                    $available,
                );
            }

            $entries = [];
            $outstanding = $qty;

            foreach ($layers as $layer) {
                if ($outstanding <= 0.0001) {
                    break;
                }
                $take = min($outstanding, $layer['remaining']);
                $outstanding -= $take;

                $entries[] = self::writeOut($spareId, -$take, $entryType, $source, $options, [
                    'rate_per_unit' => $layer['rate_per_unit'],
                    'batch_no' => $layer['batch_no'],
                    'expiry_date' => $layer['expiry_date'],
                    'layer_id' => $layer['id'],
                ]);
            }

            // Shortfall (only reachable when negative stock is allowed): recorded
            // against no layer, valued at the last rate the part came in at, so
            // the balance and the cost stay honest.
            if ($outstanding > 0.0001) {
                $entries[] = self::writeOut($spareId, -$outstanding, $entryType, $source, $options, [
                    'rate_per_unit' => StockLedger::lastInwardRate($spareId),
                    'batch_no' => null,
                    'expiry_date' => null,
                    'layer_id' => null,
                ]);
            }

            return $entries;
        });
    }

    /**
     * Post a counted difference. Positive tops stock up as a new layer; negative
     * writes it down through the layers, and is always allowed to go negative —
     * a physical count is the authority, not the ledger.
     */
    public static function adjust(
        int $spareId,
        float $difference,
        ?Model $source = null,
        array $options = [],
    ): array {
        if (abs($difference) < 0.0001) {
            return [];
        }

        if ($difference > 0) {
            $entry = self::receive(
                $spareId,
                $difference,
                $options['rate_per_unit'] ?? StockLedger::lastInwardRate($spareId),
                StockEntry::TYPE_ADJUSTMENT,
                $source,
                $options,
            );

            return $entry ? [$entry] : [];
        }

        return self::issue($spareId, abs($difference), StockEntry::TYPE_ADJUSTMENT, $source, [
            ...$options,
            'allow_negative' => true,
        ]);
    }

    /**
     * Drop every entry a document previously posted, so a re-save can repost
     * from its current lines without double-counting.
     */
    public static function reverse(Model $source): void
    {
        StockEntry::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->delete();
    }

    /**
     * @param  array{rate_per_unit: float, batch_no: ?string, expiry_date: mixed, layer_id: ?int}  $layer
     */
    protected static function writeOut(int $spareId, float $signedQty, string $entryType, ?Model $source, array $options, array $layer): StockEntry
    {
        return StockEntry::create([
            'spare_id' => $spareId,
            'entry_type' => $entryType,
            'source_type' => $source ? $source::class : null,
            'source_id' => $source?->getKey(),
            'qty' => $signedQty,
            'rate_per_unit' => $layer['rate_per_unit'],
            'batch_no' => $layer['batch_no'],
            'expiry_date' => $layer['expiry_date'],
            'layer_id' => $layer['layer_id'],
            'location' => $options['location'] ?? null,
            'moved_at' => $options['moved_at'] ?? now(),
            'actor_user_id' => auth()->id(),
            'notes' => $options['notes'] ?? null,
        ]);
    }

    /** Blank batch numbers are stored as null so they don't form their own layer. */
    protected static function normaliseBatch(?string $batch): ?string
    {
        $batch = mb_strtoupper(trim((string) $batch));

        return $batch === '' ? null : $batch;
    }
}
