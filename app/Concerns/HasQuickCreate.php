<?php

namespace App\Concerns;

use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Backs Flux's `<flux:select.option.create>` pattern (combobox + inline "Create X" option).
 *
 * Each picker on a Livewire component declares:
 *   - a public `<thing>Search` string property (bound to `<flux:select.input>`)
 *   - a public `<thing>_id` integer property (bound to the parent `<flux:select wire:model>`)
 *   - a thin action method (e.g. `createBusinessType()`) that delegates to `quickCreate()`
 *
 * Behaviour:
 *   - authorizes the given permission slug
 *   - trims + uppercases the typed search and uses `firstOrCreate` so re-typing
 *     an existing value selects it instead of erroring on the unique constraint
 *   - assigns the new id to the target property and clears the search
 *   - emits a success toast
 *
 * Special-case fields (e.g. RegionMaster needs `kind`) go through `$defaults`.
 */
trait HasQuickCreate
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $defaults  attributes set when creating a fresh row
     * @param  bool  $appendToList  when true, target property is treated as a list and the new id is appended (deduped). Use for multi-select pickers.
     */
    protected function quickCreate(
        string $modelClass,
        string $targetProperty,
        string $searchProperty,
        string $permission,
        array $defaults = ['is_active' => true],
        string $column = 'name',
        ?string $label = null,
        bool $appendToList = false,
    ): void {
        $this->authorize($permission);

        $value = strtoupper(trim((string) ($this->{$searchProperty} ?? '')));
        if ($value === '') {
            return;
        }

        /** @var Model $record */
        $record = $modelClass::firstOrCreate([$column => $value], $defaults);

        if ($appendToList) {
            $current = (array) ($this->{$targetProperty} ?? []);
            $current[] = $record->getKey();
            $this->{$targetProperty} = array_values(array_unique($current));
        } else {
            $this->{$targetProperty} = $record->getKey();
        }
        $this->{$searchProperty} = '';

        Flux::toast(
            text: ($label ?? Str::headline(class_basename($modelClass))).' "'.$record->{$column}.'" added.',
            variant: 'success',
        );
    }
}
