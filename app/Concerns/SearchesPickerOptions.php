<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Server-side option lists for pickers backed by large tables.
 *
 * Rendering a whole master into a `<flux:select>` does not scale: the customer
 * list alone is ~9,700 rows, and Livewire re-renders (and re-ships) every one
 * of those options on each round trip. These helpers keep the rendered list at
 * a handful of rows and push the filtering into the database.
 *
 * Pair with Flux's backend-search form, which turns off client-side filtering:
 *
 *     <flux:select wire:model="customer_id" variant="combobox" :filter="false">
 *         <x-slot name="input">
 *             <flux:select.input wire:model.live.debounce.250ms="customerSearch" />
 *         </x-slot>
 *         ...
 *     </flux:select>
 */
trait SearchesPickerOptions
{
    /**
     * Rows matching the search term, plus whatever is currently selected.
     *
     * The selected row must always be present or an edit form would render a
     * blank picker for a value it actually holds — the option supplying the
     * display label simply would not exist in the DOM.
     *
     * @param  Builder<Model>  $query
     * @param  array<int, string>  $searchColumns  columns matched against the term
     * @param  int|array<int, int>|null  $selected  currently selected id(s), always retained
     * @param  array<int, string>  $columns  columns to select
     * @return Collection<int, Model>
     */
    protected function pickerOptions(
        Builder $query,
        array $searchColumns,
        ?string $term,
        int|array|null $selected = null,
        array $columns = ['*'],
        int $limit = 20,
    ): Collection {
        $term = trim((string) $term);
        $selectedIds = array_values(array_filter((array) $selected));

        $matches = (clone $query)
            ->when($term !== '', function (Builder $q) use ($searchColumns, $term) {
                $q->where(function (Builder $inner) use ($searchColumns, $term) {
                    foreach ($searchColumns as $column) {
                        // Dotted entries search a relation: 'model.brand.name'
                        // becomes whereHas('model.brand', name LIKE ...).
                        if (str_contains($column, '.')) {
                            $relation = substr($column, 0, strrpos($column, '.'));
                            $field = substr($column, strrpos($column, '.') + 1);

                            $inner->orWhereHas($relation, fn (Builder $r) => $r->whereLike($field, '%'.$term.'%', caseSensitive: false));

                            continue;
                        }

                        $inner->orWhereLike($column, '%'.$term.'%', caseSensitive: false);

                        // Plates are stored spaced ("GJ 01 ZZ 9999") but typed
                        // compact, and vice versa. Compare both sides stripped so
                        // "GJ01ZZ9999" and "GJ 01 ZZ 9999" find each other.
                        if (str_ends_with($column, 'registration_no')) {
                            $compact = preg_replace('/[^A-Za-z0-9]/', '', $term);

                            if ($compact !== '') {
                                $inner->orWhereRaw(
                                    "replace(replace(replace(upper({$column}), ' ', ''), '-', ''), '.', '') like ?",
                                    ['%'.mb_strtoupper($compact).'%'],
                                );
                            }
                        }
                    }
                });
            })
            ->limit($limit)
            ->get($columns);

        if ($selectedIds === []) {
            return $matches;
        }

        $missing = array_diff($selectedIds, $matches->modelKeys());

        if ($missing === []) {
            return $matches;
        }

        // Fetch the selected rows from a *fresh* query rather than a clone: the
        // caller's query usually filters on is_active, and a record that was
        // deactivated after being chosen must still render on the edit form.
        return $query->getModel()->newQuery()
            ->whereKey($missing)
            ->get($columns)
            ->merge($matches);
    }
}
