<?php

namespace App\Concerns;

use App\Modules\Inventory\Exceptions\InsufficientStockException;

/**
 * For Livewire forms whose save moves stock.
 *
 * `StockIssuer` throws when an issue would take the ledger negative. That
 * happens inside the save transaction, so the whole document rolls back — this
 * turns the exception into a validation error on the offending line instead of
 * a 500, and puts `editingId` back where it was so a failed create does not
 * leave the form pointing at a row that was never committed.
 */
trait MovesStock
{
    protected function runStockGuarded(callable $callback): mixed
    {
        $editingIdBefore = $this->editingId ?? null;

        try {
            return $callback();
        } catch (InsufficientStockException $e) {
            $this->editingId = $editingIdBefore;
            $this->addError($this->stockErrorKey($e->spareId), $e->getMessage());

            return null;
        }
    }

    /**
     * Validation key of the line holding the spare that ran short. Override
     * when the quantity being issued lives under a different field.
     */
    protected function stockErrorKey(int $spareId): string
    {
        foreach ($this->items ?? [] as $index => $item) {
            if ((int) ($item['spare_id'] ?? 0) === $spareId) {
                return 'items.'.$index.'.qty';
            }
        }

        return 'items';
    }
}
