<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\AuditLogMaster\Livewire\Index;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the audit log index for an admin user', function () {
    SpareBrandMaster::create(['name' => 'INDEX RENDER BRAND']);

    $this->get(route('audit-log-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee('Audit Log');
});

it('forbids non-admin users without audit_log_master.view', function () {
    auth()->logout();

    // Bare user, no roles.
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('audit-log-master.index'))->assertForbidden();
});

it('filters by event type', function () {
    AuditLog::query()->delete();

    // Distinct labels avoid substring collisions in assertDontSee.
    $alpha = SpareBrandMaster::create(['name' => 'ALPHA WAS BORN']); // created
    $beta = SpareBrandMaster::create(['name' => 'BETA THE SECOND']);
    $beta->update(['name' => 'BETA RENAMED FOREVER']); // updated row carries the new label
    $gamma = SpareBrandMaster::create(['name' => 'GAMMA REMOVED SOON']);
    $gamma->delete(); // deleted

    // Only updated rows: BETA RENAMED FOREVER should be visible; ALPHA + GAMMA absent.
    Livewire::test(Index::class)
        ->set('eventFilter', 'updated')
        ->assertSee('BETA RENAMED FOREVER')
        ->assertDontSee('ALPHA WAS BORN')
        ->assertDontSee('GAMMA REMOVED SOON');

    // Only deleted rows: GAMMA REMOVED SOON visible; ALPHA + BETA absent.
    Livewire::test(Index::class)
        ->set('eventFilter', 'deleted')
        ->assertSee('GAMMA REMOVED SOON')
        ->assertDontSee('ALPHA WAS BORN')
        ->assertDontSee('BETA RENAMED FOREVER');
});

it('filters by user', function () {
    $other = User::factory()->create(['name' => 'OTHER USER']);
    $this->actingAs($other);
    SpareBrandMaster::create(['name' => 'CREATED BY OTHER']);

    $admin = adminUser(['name' => 'ADMIN ACTOR']);
    $this->actingAs($admin);
    SpareBrandMaster::create(['name' => 'CREATED BY ADMIN']);

    Livewire::test(Index::class)
        ->set('userFilter', (string) $other->id)
        ->assertSee('CREATED BY OTHER')
        ->assertDontSee('CREATED BY ADMIN');
});

it('filters by date range', function () {
    AuditLog::query()->delete();

    AuditLog::create([
        'user_id' => auth()->id(),
        'user_name' => 'tester',
        'event' => 'created',
        'model_type' => SpareBrandMaster::class,
        'model_id' => 1,
        'model_label' => 'OLD ROW',
        'created_at' => now()->subDays(10),
    ]);

    AuditLog::create([
        'user_id' => auth()->id(),
        'user_name' => 'tester',
        'event' => 'created',
        'model_type' => SpareBrandMaster::class,
        'model_id' => 2,
        'model_label' => 'RECENT ROW',
        'created_at' => now(),
    ]);

    Livewire::test(Index::class)
        ->set('dateFrom', now()->subDays(2)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->assertSee('RECENT ROW')
        ->assertDontSee('OLD ROW');
});

it('searches by model_label', function () {
    SpareBrandMaster::create(['name' => 'SEARCHABLE WIDGET']);
    SpareBrandMaster::create(['name' => 'OTHER GADGET']);

    Livewire::test(Index::class)
        ->set('search', 'WIDGET')
        ->assertSee('SEARCHABLE WIDGET')
        ->assertDontSee('OTHER GADGET');
});

it('defaults sort to created_at descending', function () {
    SpareBrandMaster::create(['name' => 'OLDEST BRAND']);
    SpareBrandMaster::create(['name' => 'NEWEST BRAND']);

    $component = Livewire::test(Index::class);

    expect($component->get('sortBy'))->toBe('created_at')
        ->and($component->get('sortDirection'))->toBe('desc');

    $html = $component->html();
    $newestPos = strpos($html, 'NEWEST BRAND');
    $oldestPos = strpos($html, 'OLDEST BRAND');

    // Newest entry appears first in the rendered table.
    expect($newestPos)->not->toBeFalse()
        ->and($oldestPos)->not->toBeFalse()
        ->and($newestPos)->toBeLessThan($oldestPos);
});

it('renders the detail modal diff for an updated row', function () {
    $brand = SpareBrandMaster::create(['name' => 'BEFORE NAME']);
    $brand->update(['name' => 'AFTER NAME']);

    $log = AuditLog::query()->where('event', 'updated')->latest('id')->firstOrFail();

    $html = Livewire::test(Index::class)->html();

    // The modal markup is rendered inline inside the row; ensure both old and new values are present.
    expect($html)->toContain('audit-log-detail-'.$log->id)
        ->and($html)->toContain('BEFORE NAME')
        ->and($html)->toContain('AFTER NAME');
});
