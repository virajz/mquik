<?php

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\JobCard\Livewire\Edit;
use App\Modules\JobCard\Models\JobCard;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

/** Build a past visit on the vehicle carrying one complaint. */
function pastVisit(CustomerVehicleMaster $vehicle, string $service, string $openedAt, ?int $km = null): JobCard
{
    $card = JobCard::factory()->create([
        'customer_vehicle_id' => $vehicle->id,
        'opened_at' => $openedAt,
        'km_at_service' => $km,
    ]);
    $card->complaints()->create(['description' => $service, 'sequence_no' => 1]);

    return $card;
}

it('lists what the vehicle has had done with the date it was last done', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();
    pastVisit($vehicle, 'OIL CHANGE', '2026-01-10 09:00:00', 30000);
    pastVisit($vehicle, 'OIL CHANGE', '2026-06-10 09:00:00', 40000);
    pastVisit($vehicle, 'WHEEL ALIGNMENT', '2026-03-01 09:00:00', 35000);

    $current = JobCard::factory()->create(['customer_vehicle_id' => $vehicle->id]);

    $history = Livewire::test(Edit::class, ['jobCard' => $current])->instance()->serviceHistory;
    $byService = $history->keyBy('service');

    expect($byService)->toHaveKey('OIL CHANGE')
        // Latest of the two oil changes, and counted twice.
        ->and($byService['OIL CHANGE']['last_done_at']->format('Y-m-d'))->toBe('2026-06-10')
        ->and($byService['OIL CHANGE']['times'])->toBe(2)
        ->and($byService['OIL CHANGE']['last_km'])->toBe(40000)
        ->and($byService['WHEEL ALIGNMENT']['times'])->toBe(1);
});

it('filters past visits down to the searched service', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();
    $oil = pastVisit($vehicle, 'OIL CHANGE', '2026-01-10 09:00:00');
    $align = pastVisit($vehicle, 'WHEEL ALIGNMENT', '2026-03-01 09:00:00');

    $current = JobCard::factory()->create(['customer_vehicle_id' => $vehicle->id]);

    $component = Livewire::test(Edit::class, ['jobCard' => $current])
        ->set('historySearch', 'OIL CHANGE');

    $ids = $component->instance()->vehicleJobCards->pluck('id');

    expect($ids)->toContain($oil->id)
        ->and($ids)->not->toContain($align->id);
});

it('excludes the card being viewed from its own history', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();
    $current = JobCard::factory()->create(['customer_vehicle_id' => $vehicle->id]);
    $current->complaints()->create(['description' => 'CURRENT WORK', 'sequence_no' => 1]);

    $component = Livewire::test(Edit::class, ['jobCard' => $current]);

    expect($component->instance()->vehicleJobCards->pluck('id'))->not->toContain($current->id);
});
