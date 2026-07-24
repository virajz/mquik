<?php

use App\Modules\VehicleModelMaster\Support\ServiceSchedule;
use Carbon\CarbonImmutable;

it('is due by time when only a month interval is set', function () {
    $last = CarbonImmutable::parse('2026-01-01');

    $r = ServiceSchedule::nextDue(['km' => null, 'months' => 12], $last);

    expect($r['due_at']->toDateString())->toBe('2027-01-01')
        ->and($r['by'])->toBe('time')
        ->and($r['due_km'])->toBeNull();
});

it('translates the km interval into a date via average daily distance', function () {
    $last = CarbonImmutable::parse('2026-01-01');

    // 10,000 km at 50 km/day = 200 days.
    $r = ServiceSchedule::nextDue(['km' => 10000, 'months' => null], $last, lastServiceOdometer: 30000, avgKmPerDay: 50);

    expect($r['due_km'])->toBe(40000)
        ->and($r['due_at']->toDateString())->toBe($last->addDays(200)->toDateString())
        ->and($r['by'])->toBe('km');
});

it('picks whichever limb comes first', function () {
    $last = CarbonImmutable::parse('2026-01-01');

    // Heavy use: km limb (100 days) beats the 12-month limb.
    $heavy = ServiceSchedule::nextDue(['km' => 10000, 'months' => 12], $last, 0, 100);
    expect($heavy['by'])->toBe('km')
        ->and($heavy['due_at']->toDateString())->toBe($last->addDays(100)->toDateString());

    // Light use: 12 months beats the km limb (~1000 days).
    $light = ServiceSchedule::nextDue(['km' => 10000, 'months' => 12], $last, 0, 10);
    expect($light['by'])->toBe('time')
        ->and($light['due_at']->toDateString())->toBe('2027-01-01');
});

it('cannot date the km limb without odometer and daily distance', function () {
    $r = ServiceSchedule::nextDue(['km' => 10000, 'months' => null], CarbonImmutable::parse('2026-01-01'));

    expect($r['due_at'])->toBeNull()
        ->and($r['due_km'])->toBeNull();
});

it('returns nulls when no interval is configured at all', function () {
    $r = ServiceSchedule::nextDue(['km' => null, 'months' => null], CarbonImmutable::parse('2026-01-01'), 20000, 40);

    expect($r['due_at'])->toBeNull()->and($r['by'])->toBeNull();
});
