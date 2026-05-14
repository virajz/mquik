<?php

namespace App\Support;

use Illuminate\Support\Collection;

class Menu
{
    public const MODE_OPERATIONS = 'operations';

    public const MODE_SETUP = 'setup';

    /** @var array<int, array{group:string,label:string,icon:?string,route:?string,permission:?string,order:int,module:string,mode:string}> */
    protected array $items = [];

    public function add(array $item): void
    {
        $this->items[] = array_merge([
            'group' => 'General',
            'label' => '',
            'icon' => null,
            'route' => null,
            'permission' => null,
            'order' => 100,
            'module' => '',
            'mode' => self::MODE_SETUP,
        ], $item);
    }

    /**
     * @return Collection<string, Collection<int, array>>
     *                                                    Grouped, permission-filtered, sorted menu for the current user.
     *                                                    Optionally filtered to a single mode (operations|setup).
     */
    public function forCurrentUser(?string $mode = null): Collection
    {
        return collect($this->items)
            ->filter(fn ($item) => $this->userHasAccess($item['permission']))
            ->when($mode !== null, fn ($items) => $items->filter(fn ($item) => $item['mode'] === $mode))
            ->sortBy('order')
            ->groupBy('group');
    }

    /**
     * Distinct modes that have at least one item the current user can access.
     * Used to hide the sidebar mode toggle when only one mode is populated.
     *
     * @return Collection<int, string>
     */
    public function availableModes(): Collection
    {
        return collect($this->items)
            ->filter(fn ($item) => $this->userHasAccess($item['permission']))
            ->pluck('mode')
            ->unique()
            ->values();
    }

    /**
     * Resolve the mode of the route the user is currently on, if any.
     * Used to position the toggle on first paint so the user lands on the
     * pill matching their current page.
     */
    public function activeMode(): ?string
    {
        $current = request()->route()?->getName();
        if (! $current) {
            return null;
        }

        foreach ($this->items as $item) {
            if ($item['route'] === $current) {
                return $item['mode'];
            }
        }

        return null;
    }

    public function all(): Collection
    {
        return collect($this->items);
    }

    /**
     * Section tabs for the secondary header — one per unique menu group,
     * each pointing to the first module's route in that group.
     *
     * @return Collection<int, array{label:string,route:?string,group:string}>
     */
    public function sectionTabs(): Collection
    {
        return $this->forCurrentUser()
            ->map(fn ($items, $group) => [
                'label' => $group,
                'group' => $group,
                'route' => $items->first()['route'] ?? null,
            ])
            ->values();
    }

    /**
     * Resolve the active group based on the current request's route.
     * Used to highlight the correct tab in the section navbar.
     */
    public function activeGroup(): ?string
    {
        $current = request()->route()?->getName();
        if (! $current) {
            return null;
        }

        foreach ($this->items as $item) {
            if ($item['route'] === $current) {
                return $item['group'];
            }
        }

        return null;
    }

    protected function userHasAccess(?string $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Spatie's HasRoles trait is on User; can() flows through Gate::before for super-admin
        // checks and falls back to permission lookup. Returns false if the permission isn't
        // registered yet (e.g. before the first auth:sync-permissions run).
        return $user->can($permission);
    }
}
