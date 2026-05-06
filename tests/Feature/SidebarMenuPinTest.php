<?php

use App\Livewire\SidebarMenu;
use App\Models\User;
use App\Models\UserMenuPin;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders without errors when no pins exist', function () {
    Livewire::test(SidebarMenu::class)
        ->assertOk();
});

it('pins a route for the current user', function () {
    Livewire::test(SidebarMenu::class)
        ->call('togglePin', 'dashboard', 'Dashboard', 'home');

    expect(UserMenuPin::where('user_id', auth()->id())->count())->toBe(1)
        ->and(UserMenuPin::first()->route_name)->toBe('dashboard');
});

it('unpins a route by toggling again', function () {
    Livewire::test(SidebarMenu::class)
        ->call('togglePin', 'dashboard', 'Dashboard', 'home')
        ->call('togglePin', 'dashboard', 'Dashboard', 'home');

    expect(UserMenuPin::count())->toBe(0);
});

it('assigns incrementing positions to new pins', function () {
    Livewire::test(SidebarMenu::class)
        ->call('togglePin', 'dashboard', 'Dashboard', 'home')
        ->call('togglePin', 'employee-master.index', 'Employees', 'user-group');

    $pins = UserMenuPin::orderBy('position')->get();
    expect($pins->pluck('route_name')->all())->toBe(['dashboard', 'employee-master.index'])
        ->and($pins->pluck('position')->all())->toBe([1, 2]);
});

it('reorders pins via reorderPins', function () {
    $userId = auth()->id();
    $a = UserMenuPin::create(['user_id' => $userId, 'route_name' => 'a', 'label' => 'A', 'position' => 1]);
    $b = UserMenuPin::create(['user_id' => $userId, 'route_name' => 'b', 'label' => 'B', 'position' => 2]);
    $c = UserMenuPin::create(['user_id' => $userId, 'route_name' => 'c', 'label' => 'C', 'position' => 3]);

    Livewire::test(SidebarMenu::class)
        ->call('reorderPins', [(string) $c->id, (string) $a->id, (string) $b->id]);

    $order = UserMenuPin::orderBy('position')->pluck('route_name')->all();
    expect($order)->toBe(['c', 'a', 'b']);
});

it('reorderPins ignores ids that belong to a different user', function () {
    $other = User::factory()->create();
    $foreign = UserMenuPin::create(['user_id' => $other->id, 'route_name' => 'foreign', 'label' => 'F', 'position' => 1]);

    $userId = auth()->id();
    $a = UserMenuPin::create(['user_id' => $userId, 'route_name' => 'a', 'label' => 'A', 'position' => 1]);
    $b = UserMenuPin::create(['user_id' => $userId, 'route_name' => 'b', 'label' => 'B', 'position' => 2]);

    Livewire::test(SidebarMenu::class)
        ->call('reorderPins', [(string) $foreign->id, (string) $b->id, (string) $a->id]);

    expect($foreign->fresh()->position)->toBe(1) // unchanged
        ->and($b->fresh()->position)->toBe(1)
        ->and($a->fresh()->position)->toBe(2);
});

it('reorderPins handles a no-op safely', function () {
    $userId = auth()->id();
    $a = UserMenuPin::create(['user_id' => $userId, 'route_name' => 'a', 'label' => 'A', 'position' => 1]);

    Livewire::test(SidebarMenu::class)->call('reorderPins', []);

    expect($a->fresh()->position)->toBe(1);
});

it('scopes pins to the current user', function () {
    $other = User::factory()->create();
    UserMenuPin::create(['user_id' => $other->id, 'route_name' => 'dashboard', 'label' => 'Dashboard', 'position' => 1]);

    Livewire::test(SidebarMenu::class)
        ->call('togglePin', 'dashboard', 'Dashboard', 'home');

    expect(UserMenuPin::count())->toBe(2)
        ->and(UserMenuPin::where('user_id', auth()->id())->count())->toBe(1);
});

it('renders pinned section when pins exist', function () {
    UserMenuPin::create([
        'user_id' => auth()->id(),
        'route_name' => 'dashboard',
        'label' => 'My Dashboard',
        'icon' => 'home',
        'position' => 1,
    ]);

    Livewire::test(SidebarMenu::class)
        ->assertSee('Pinned')
        ->assertSee('Dashboard');
});

it('hides pinned items that no longer exist in the menu', function () {
    UserMenuPin::create([
        'user_id' => auth()->id(),
        'route_name' => 'nonexistent.route',
        'label' => 'Gone',
        'icon' => 'cube',
        'position' => 1,
    ]);

    Livewire::test(SidebarMenu::class)
        ->assertDontSee('Pinned')
        ->assertDontSee('Gone');
});
