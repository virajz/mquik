<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Lets a module index be deep-linked to a single customer, vehicle or job card
 * — the landing half of the record panel (`App\Support\RelatedRecords`).
 *
 * A component opts in by using the trait and calling `applyRecordScope()` in its
 * query. Only the filters whose column actually exists on the table are applied,
 * so one trait serves indexes with different shapes.
 *
 * The active scope is surfaced to the view via `recordScopeLabel()` so the page
 * can show the user why they are seeing a subset.
 */
trait ScopesToRecord
{
    #[Url(as: 'customer')]
    public ?int $scopeCustomerId = null;

    #[Url(as: 'vehicle')]
    public ?int $scopeVehicleId = null;

    #[Url(as: 'job_card')]
    public ?int $scopeJobCardId = null;

    /**
     * Narrow a query to whichever record the URL named.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    protected function applyRecordScope(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();

        $filters = [
            'customer_id' => $this->scopeCustomerId,
            'customer_vehicle_id' => $this->scopeVehicleId,
            'job_card_id' => $this->scopeJobCardId,
        ];

        foreach ($filters as $column => $value) {
            if ($value && $query->getConnection()->getSchemaBuilder()->hasColumn($table, $column)) {
                $query->where($table.'.'.$column, $value);
            }
        }

        return $query;
    }

    /** True when the page is showing a record-scoped subset. */
    public function hasRecordScope(): bool
    {
        return (bool) ($this->scopeCustomerId || $this->scopeVehicleId || $this->scopeJobCardId);
    }

    /** Drop the record scope and go back to the full list. */
    public function clearRecordScope(): void
    {
        $this->scopeCustomerId = null;
        $this->scopeVehicleId = null;
        $this->scopeJobCardId = null;

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
