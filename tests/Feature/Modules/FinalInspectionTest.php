<?php

use App\Modules\FinalInspection\Livewire\Edit;
use App\Modules\FinalInspection\Livewire\Index;
use App\Modules\FinalInspection\Models\FinalInspection;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\StandardObservationMaster\Models\StandardObservationMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    FinalInspection::factory()->count(2)->create();

    $this->get(route('final-inspection.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('final-inspection.index'))->assertRedirect(route('login'));
});

it('creates a final inspection, stamps FI number, and redirects', function () {
    $jc = JobCard::factory()->create();

    Livewire::test(Edit::class)
        ->set('job_card_id', $jc->id)
        ->set('status', 'in_progress')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $fi = FinalInspection::firstOrFail();
    expect($fi->inspection_no)->toBe('FI-'.str_pad((string) $fi->id, 5, '0', STR_PAD_LEFT))
        ->and($fi->started_at)->not->toBeNull(); // stamped when moving to in_progress
});

it('snapshots template checkpoints into the checklist', function () {
    $template = InspectionTemplateMaster::factory()->create(['is_active' => true]);
    $a = InspectionItemMaster::factory()->create(['name' => 'BRAKE TEST']);
    $b = InspectionItemMaster::factory()->create(['name' => 'PAINT FINISH']);
    $template->items()->attach([$a->id => ['position' => 1], $b->id => ['position' => 2]]);

    $component = Livewire::test(Edit::class)->set('inspection_template_id', $template->id);

    expect($component->get('items'))->toHaveCount(2)
        ->and($component->get('items')[0]['label'])->toBe('BRAKE TEST')
        ->and($component->get('items')[0]['result'])->toBe('pending');
});

it('saves checklist items with result, observation and before/after/damage photos', function () {
    Storage::fake('public');
    StandardObservationMaster::factory()->create(['name' => 'MINOR SCRATCH']);
    $fi = FinalInspection::factory()->create();

    Livewire::test(Edit::class, ['finalInspection' => $fi])
        ->set('items', [
            ['id' => null, 'inspection_item_id' => null, 'inspection_item_group_id' => null, 'label' => 'brake test', 'group_name' => null, 'result' => 'ok', 'recommendation' => 'none', 'severity' => 'low', 'observation' => 'minor scratch', 'notes' => null, 'before_photo_path' => null, 'after_photo_path' => null, 'damage_photo_path' => null, 'sequence_no' => 1],
        ])
        ->set('itemBeforeFiles.0', UploadedFile::fake()->image('before.jpg'))
        ->set('itemDamageFiles.0', UploadedFile::fake()->image('damage.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $fi->refresh()->load('items');
    $item = $fi->items->first();
    expect($item->result)->toBe('ok')
        ->and($item->observation)->toBe('MINOR SCRATCH')
        ->and($item->before_photo_path)->not->toBeNull()
        ->and($item->damage_photo_path)->not->toBeNull()
        ->and($item->after_photo_path)->toBeNull();
    Storage::disk('public')->assertExists($item->before_photo_path);
    Storage::disk('public')->assertExists($item->damage_photo_path);
});

it('saves the pause/time log', function () {
    $fi = FinalInspection::factory()->create();

    Livewire::test(Edit::class, ['finalInspection' => $fi])
        ->set('pauses', [
            ['id' => null, 'paused_date' => '2026-06-21', 'paused_time' => '11:00', 'resumed_date' => '2026-06-21', 'resumed_time' => '11:15', 'notes' => 'tea'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $fi->refresh()->load('pauses');
    expect($fi->pauses)->toHaveCount(1)
        ->and($fi->pauses->first()->notes)->toBe('TEA');
});

it('deletes a final inspection from the index', function () {
    $fi = FinalInspection::factory()->create();

    Livewire::test(Index::class)->call('delete', $fi->id);

    expect(FinalInspection::find($fi->id))->toBeNull();
});
