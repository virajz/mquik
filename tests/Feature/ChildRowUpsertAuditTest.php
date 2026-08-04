<?php

use App\Support\ChildRows;
use App\Modules\SpareMaster\Models\SpareMaster;

/**
 * Guards a bug the test suite structurally cannot catch.
 *
 * `updateOrCreate(['id' => $row['id'] ?? null], $payload)` runs `firstOrNew()`,
 * which SETS a null id on the model — so it reaches the database as
 * `insert ("id", …) values (NULL, …)`. SQLite treats an explicit null primary
 * key as auto-assign, so every feature test passes; Postgres raises a not-null
 * violation and the save dies in production.
 *
 * It went unnoticed across 72 call sites in 45 modules. Since no amount of
 * feature testing on SQLite will surface it, this asserts on the source.
 */
it('never keys updateOrCreate on a possibly-null id', function () {
    $offenders = [];

    foreach (glob(base_path('app/Modules/*/Livewire/*.php')) as $file) {
        $lines = file($file);
        foreach ($lines as $i => $line) {
            if (! str_contains($line, '->updateOrCreate(')) {
                continue;
            }
            // The key array is either on this line or the next one.
            $window = $line.($lines[$i + 1] ?? '');
            if (preg_match("/\['id'\s*=>/", $window)) {
                $offenders[] = str_replace(base_path().'/', '', $file).':'.($i + 1);
            }
        }
    }

    expect($offenders)->toBe([], implode("\n", [
        'Use ChildRows::upsert($relation, $id, $payload) instead — keying updateOrCreate',
        'on a nullable id writes an explicit NULL primary key, which Postgres rejects:',
        ...$offenders,
    ]));
});

it('creates a child row without naming the primary key in the insert', function () {
    $spare = SpareMaster::factory()->create();

    $queries = [];
    DB::listen(function ($q) use (&$queries) {
        $queries[] = $q->sql;
    });

    ChildRows::upsert($spare->attachments(), null, [
        'attachment_type' => 'spare_image', 'path' => 'x.jpg', 'original_name' => 'x.jpg', 'sequence_no' => 1,
    ]);

    $insert = collect($queries)->first(fn ($sql) => str_starts_with($sql, 'insert into'));
    expect($insert)->not->toContain('"id"')
        ->and($spare->attachments()->count())->toBe(1);
});

it('updates in place when an id is given rather than inserting again', function () {
    $spare = SpareMaster::factory()->create();

    $created = ChildRows::upsert($spare->attachments(), null, [
        'attachment_type' => 'spare_image', 'path' => 'a.jpg', 'original_name' => 'a.jpg', 'sequence_no' => 1,
    ]);
    $updated = ChildRows::upsert($spare->attachments(), $created->id, [
        'attachment_type' => 'application_guide', 'path' => 'b.pdf', 'original_name' => 'b.pdf', 'sequence_no' => 1,
    ]);

    expect($updated->id)->toBe($created->id)
        ->and($updated->attachment_type)->toBe('application_guide')
        ->and($spare->attachments()->count())->toBe(1);
});

it('creates rather than throwing when the given id belongs to nothing', function () {
    $spare = SpareMaster::factory()->create();

    $row = ChildRows::upsert($spare->attachments(), 999999, [
        'attachment_type' => 'spare_image', 'path' => 'c.jpg', 'original_name' => 'c.jpg', 'sequence_no' => 1,
    ]);

    expect($row->exists)->toBeTrue()
        ->and($row->id)->not->toBe(999999)
        ->and($row->spare_id)->toBe($spare->id);
});
