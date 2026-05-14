<?php

use App\Models\User;
use App\Modules\DigitalInspection\Livewire\Edit;
use App\Modules\DigitalInspection\Livewire\Index;
use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\DigitalInspection\Models\DigitalInspectionItem;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    DigitalInspection::factory()->count(3)->create();

    $this->get(route('digital-inspection.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('auto-stamps DI-00001 style inspection_no on create', function () {
    $row = DigitalInspection::factory()->create();

    expect($row->fresh()->inspection_no)->toBe('DI-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT));
});

it('seeds template items into the form when template is selected', function () {
    $template = InspectionTemplateMaster::factory()->create();
    $a = InspectionItemMaster::factory()->create(['name' => 'BRAKES']);
    $b = InspectionItemMaster::factory()->create(['name' => 'TYRE TREAD']);
    $c = InspectionItemMaster::factory()->create(['name' => 'BATTERY']);
    $template->items()->attach([$a->id, $b->id, $c->id]);

    Livewire::test(Edit::class)
        ->set('inspection_template_id', $template->id)
        ->assertCount('items', 3);
});

it('does not re-seed items when editing an existing inspection', function () {
    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create();
    $template->items()->attach([$item->id]);

    $di = DigitalInspection::factory()->create(['inspection_template_id' => $template->id]);
    $di->items()->create(['inspection_item_id' => $item->id, 'outcome' => 'ok', 'notes' => 'EXISTING NOTE', 'sequence_no' => 1]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->assertCount('items', 1)
        ->set('inspection_template_id', $template->id)  // attempt to re-seed
        ->assertCount('items', 1);                       // still 1
});

it('persists item outcomes and notes on save', function () {
    $jc = JobCard::factory()->create();
    $template = InspectionTemplateMaster::factory()->create();
    $a = InspectionItemMaster::factory()->create(['name' => 'BRAKES']);
    $b = InspectionItemMaster::factory()->create(['name' => 'TYRES']);
    $template->items()->attach([$a->id, $b->id]);

    Livewire::test(Edit::class)
        ->set('job_card_id', $jc->id)
        ->set('inspection_template_id', $template->id)
        ->set('items.0.outcome', 'rep')
        ->set('items.0.notes', 'pad worn through')
        ->set('items.1.outcome', 'ok')
        ->call('save')
        ->assertHasNoErrors();

    $di = DigitalInspection::with('items')->first();
    expect($di->items)->toHaveCount(2)
        ->and($di->items[0]->outcome)->toBe('rep')
        ->and($di->items[0]->notes)->toBe('PAD WORN THROUGH')
        ->and($di->items[1]->outcome)->toBe('ok')
        ->and($di->inspection_no)->toStartWith('DI-');
});

it('stamps started_at when status moves to wip on update', function () {
    $jc = JobCard::factory()->create();
    $template = InspectionTemplateMaster::factory()->create();
    $di = DigitalInspection::factory()->create([
        'job_card_id' => $jc->id,
        'inspection_template_id' => $template->id,
        'status' => DigitalInspection::STATUS_PENDING,
        'started_at' => null,
    ]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->set('status', DigitalInspection::STATUS_WIP)
        ->call('save')
        ->assertHasNoErrors();

    expect($di->fresh()->started_at)->not->toBeNull();
});

it('stamps completed_at when status moves to completed', function () {
    $jc = JobCard::factory()->create();
    $template = InspectionTemplateMaster::factory()->create();
    $di = DigitalInspection::factory()->wip()->create([
        'job_card_id' => $jc->id,
        'inspection_template_id' => $template->id,
    ]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->set('status', DigitalInspection::STATUS_COMPLETED)
        ->call('save')
        ->assertHasNoErrors();

    expect($di->fresh()->completed_at)->not->toBeNull();
});

it('filters by status and template', function () {
    $template = InspectionTemplateMaster::factory()->create();
    DigitalInspection::factory()->create();
    DigitalInspection::factory()->wip()->create();
    DigitalInspection::factory()->create(['inspection_template_id' => $template->id]);

    Livewire::test(Index::class)
        ->set('statusFilter', 'wip')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('statusFilter', 'all')
        ->set('templateFilter', (string) $template->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('Edit::save blocks a user without create permission', function () {
    $jc = JobCard::factory()->create();
    $template = InspectionTemplateMaster::factory()->create();

    $user = User::factory()->create();
    $user->givePermissionTo('digital_inspection.view');
    $this->actingAs($user);

    Livewire::test(Edit::class)
        ->set('job_card_id', $jc->id)
        ->set('inspection_template_id', $template->id)
        ->call('save')
        ->assertStatus(403);

    expect(DigitalInspection::count())->toBe(0);
});

it('deletes an inspection and cascades items', function () {
    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create();
    $di = DigitalInspection::factory()->create(['inspection_template_id' => $template->id]);
    $di->items()->create(['inspection_item_id' => $item->id, 'outcome' => 'ok', 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $di->id);

    expect(DigitalInspection::find($di->id))->toBeNull()
        ->and(DigitalInspectionItem::where('digital_inspection_id', $di->id)->count())->toBe(0);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('digital-inspection.index'))->assertRedirect(route('login'));
});

it('saves a per-item evidence image and stamps image_path on the row', function () {
    Storage::fake('public');

    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create(['name' => 'BRAKES']);
    $template->items()->attach([$item->id]);
    $jobCard = JobCard::factory()->create();

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->set('inspection_template_id', $template->id)  // seeds $items
        ->set("itemImages.{$item->id}", UploadedFile::fake()->image('brakes-worn.jpg', 800, 600))
        ->set('items.0.outcome', 'rep')
        ->call('save')
        ->assertHasNoErrors();

    $persistedItem = DigitalInspectionItem::query()->first();
    expect($persistedItem->image_path)->not->toBeNull();
    expect($persistedItem->image_path)->toStartWith('digital-inspections/');
    Storage::disk('public')->assertExists($persistedItem->image_path);
});

it('removes a saved item image and deletes the file when removeItemImage + save', function () {
    Storage::fake('public');

    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create();
    $template->items()->attach([$item->id]);

    $di = DigitalInspection::factory()->create(['inspection_template_id' => $template->id]);
    $stored = UploadedFile::fake()->image('old.jpg')->store("digital-inspections/{$di->id}/items", 'public');
    $diItem = $di->items()->create([
        'inspection_item_id' => $item->id,
        'outcome' => 'ok',
        'sequence_no' => 1,
        'image_path' => $stored,
    ]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->call('removeItemImage', $item->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($diItem->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($stored);
});

it('replaces a saved item image and deletes the old file', function () {
    Storage::fake('public');

    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create();
    $template->items()->attach([$item->id]);

    $di = DigitalInspection::factory()->create(['inspection_template_id' => $template->id]);
    $oldPath = UploadedFile::fake()->image('old.jpg')->store("digital-inspections/{$di->id}/items", 'public');
    $diItem = $di->items()->create([
        'inspection_item_id' => $item->id,
        'outcome' => 'ok',
        'sequence_no' => 1,
        'image_path' => $oldPath,
    ]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->set("itemImages.{$item->id}", UploadedFile::fake()->image('new.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $newPath = $diItem->fresh()->image_path;
    expect($newPath)->not->toBe($oldPath)
        ->and($newPath)->not->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);
});

it('rejects an oversized item image', function () {
    Storage::fake('public');

    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create();
    $template->items()->attach([$item->id]);
    $jobCard = JobCard::factory()->create();

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->set('inspection_template_id', $template->id)
        ->set("itemImages.{$item->id}", UploadedFile::fake()->image('huge.jpg')->size(9000))  // 9 MB > 8 MB cap
        ->call('save')
        ->assertHasErrors(['itemImages.'.$item->id]);

    expect(DigitalInspection::count())->toBe(0);
});
