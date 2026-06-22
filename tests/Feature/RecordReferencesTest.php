<?php

use App\Modules\JobCard\Models\JobCard;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\RecordReferences;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('reports the tables that reference a record', function () {
    $dept = WorkshopDepartmentMaster::factory()->create();
    JobCard::factory()->count(2)->create(['workshop_department_id' => $dept->id]);

    $usages = RecordReferences::for($dept);

    expect($usages)->toHaveKey('Job Cards')
        ->and($usages['Job Cards'])->toBe(2);
});

it('summarises references into a human sentence', function () {
    $dept = WorkshopDepartmentMaster::factory()->create();
    JobCard::factory()->create(['workshop_department_id' => $dept->id]);

    expect(RecordReferences::summary($dept))->toContain('Job Card');
});

it('returns null/empty when nothing references the record', function () {
    $dept = WorkshopDepartmentMaster::factory()->create();

    expect(RecordReferences::for($dept))->toBe([])
        ->and(RecordReferences::summary($dept))->toBeNull();
});
