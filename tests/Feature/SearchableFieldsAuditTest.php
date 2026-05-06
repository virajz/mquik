<?php

use App\Support\SearchRegistry;
use Illuminate\Support\Facades\Schema;

it('every searchable field exists as a real column on its model', function () {
    $sources = app(SearchRegistry::class)->sources();

    $missing = [];

    foreach ($sources as $source) {
        $modelClass = $source['model'];
        if (! class_exists($modelClass)) {
            continue;
        }
        $model = new $modelClass;
        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);

        foreach ($modelClass::searchableFields() as $field) {
            if (! in_array($field, $columns, true)) {
                $missing[] = "{$modelClass}::\$searchableFields includes '{$field}' but {$table} has no such column. Available: ".implode(', ', $columns);
            }
        }
    }

    expect($missing)->toBe([], implode("\n", $missing));
});
