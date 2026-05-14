<?php

use App\Livewire\SidebarMenu;
use App\Support\Menu;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('hides the mode toggle when only one mode has items', function () {
    app()->forgetInstance(Menu::class);
    $menu = app(Menu::class);
    $menu->add(['group' => 'Customers', 'label' => 'Customers', 'route' => 'customer-master.index', 'mode' => 'setup']);

    Livewire::test(SidebarMenu::class)
        ->assertViewHas('showToggle', false);
});

it('shows the mode toggle when both modes have items', function () {
    app()->forgetInstance(Menu::class);
    $menu = app(Menu::class);
    $menu->add(['group' => 'Customers', 'label' => 'Customers', 'route' => 'customer-master.index', 'mode' => 'setup']);
    $menu->add(['group' => 'Workshop', 'label' => 'Job Cards', 'route' => 'dashboard', 'mode' => 'operations']);

    Livewire::test(SidebarMenu::class)
        ->assertViewHas('showToggle', true);
});

it('filters items by the active mode when toggle is on and search is empty', function () {
    app()->forgetInstance(Menu::class);
    $menu = app(Menu::class);
    $menu->add(['group' => 'Customers', 'label' => 'Customers Master', 'route' => 'customer-master.index', 'mode' => 'setup']);
    $menu->add(['group' => 'Workshop', 'label' => 'Job Cards Operations', 'route' => 'dashboard', 'mode' => 'operations']);

    Livewire::test(SidebarMenu::class)
        ->set('mode', 'setup')
        ->assertSee('Customers Master')
        ->assertDontSee('Job Cards Operations');

    Livewire::test(SidebarMenu::class)
        ->set('mode', 'operations')
        ->assertSee('Job Cards Operations')
        ->assertDontSee('Customers Master');
});

it('search shows results from both modes regardless of toggle position', function () {
    app()->forgetInstance(Menu::class);
    $menu = app(Menu::class);
    $menu->add(['group' => 'Customers', 'label' => 'Acme Customers', 'route' => 'customer-master.index', 'mode' => 'setup']);
    $menu->add(['group' => 'Workshop', 'label' => 'Acme Job Cards', 'route' => 'dashboard', 'mode' => 'operations']);

    Livewire::test(SidebarMenu::class)
        ->set('mode', 'setup')
        ->set('search', 'Acme')
        ->assertSee('Acme Customers')
        ->assertSee('Acme Job Cards');
});

it('setMode flips the active mode and rejects invalid values', function () {
    app()->forgetInstance(Menu::class);
    $menu = app(Menu::class);
    $menu->add(['group' => 'Customers', 'label' => 'C', 'route' => 'customer-master.index', 'mode' => 'setup']);
    $menu->add(['group' => 'Workshop', 'label' => 'W', 'route' => 'dashboard', 'mode' => 'operations']);

    Livewire::test(SidebarMenu::class)
        ->call('setMode', 'setup')
        ->assertSet('mode', 'setup')
        ->call('setMode', 'operations')
        ->assertSet('mode', 'operations')
        ->call('setMode', 'garbage')
        ->assertSet('mode', 'operations');
});

it('Menu::add defaults to setup mode when not specified', function () {
    app()->forgetInstance(Menu::class);
    $menu = app(Menu::class);
    $menu->add(['group' => 'X', 'label' => 'X', 'route' => 'dashboard']);

    expect($menu->all()->first()['mode'])->toBe('setup');
});

it('Menu::availableModes returns only modes with items the user can access', function () {
    app()->forgetInstance(Menu::class);
    $menu = app(Menu::class);
    $menu->add(['group' => 'Customers', 'label' => 'C', 'route' => 'customer-master.index', 'mode' => 'setup']);
    $menu->add(['group' => 'Workshop', 'label' => 'W', 'route' => 'dashboard', 'mode' => 'operations']);

    expect($menu->availableModes()->all())->toEqualCanonicalizing(['setup', 'operations']);
});

it('every shipped module menu.php declares a mode', function () {
    foreach (glob(app_path('Modules/*/menu.php')) as $path) {
        $entries = require $path;
        foreach ($entries as $entry) {
            expect($entry)->toHaveKey('mode');
            expect($entry['mode'])->toBeIn(['setup', 'operations']);
        }
    }
});
