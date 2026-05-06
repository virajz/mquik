<?php

namespace App\Livewire;

use App\Models\UserMenuPin;
use App\Support\Menu;
use Livewire\Component;

class SidebarMenu extends Component
{
    public string $search = '';

    public function clear(): void
    {
        $this->search = '';
    }

    public function togglePin(string $route, string $label, ?string $icon = null): void
    {
        if (! $userId = auth()->id()) {
            return;
        }

        $existing = UserMenuPin::query()
            ->where('user_id', $userId)
            ->where('route_name', $route)
            ->first();

        if ($existing) {
            $existing->delete();

            return;
        }

        $nextPosition = (int) UserMenuPin::query()
            ->where('user_id', $userId)
            ->max('position') + 1;

        UserMenuPin::create([
            'user_id' => $userId,
            'route_name' => $route,
            'label' => $label,
            'icon' => $icon,
            'position' => $nextPosition,
        ]);
    }

    /**
     * @param  array<int, int|string>  $orderedIds  pin ids in their new visual order
     */
    public function reorderPins(array $orderedIds): void
    {
        if (! $userId = auth()->id()) {
            return;
        }

        $ids = array_values(array_filter(array_map('intval', $orderedIds)));
        if (empty($ids)) {
            return;
        }

        $owned = UserMenuPin::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        // Phase 1: park everything at negative positions to dodge the (user_id, position) unique.
        UserMenuPin::query()
            ->where('user_id', $userId)
            ->whereIn('id', $owned)
            ->each(function (UserMenuPin $p, int $i): void {
                $p->update(['position' => -1 - $i]);
            });

        // Phase 2: assign final positions in the requested order.
        $position = 1;
        foreach ($ids as $id) {
            if (! in_array($id, $owned, true)) {
                continue;
            }
            UserMenuPin::query()
                ->where('user_id', $userId)
                ->where('id', $id)
                ->update(['position' => $position++]);
        }
    }

    public function render()
    {
        $q = strtolower(trim($this->search));

        $groups = app(Menu::class)->forCurrentUser();

        $platformItems = collect([
            ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard'],
        ]);

        $allRoutes = collect();
        $platformItems->each(fn ($i) => $allRoutes->put($i['route'], $i));
        $groups->each(function ($items) use ($allRoutes) {
            $items->each(fn ($i) => $allRoutes->put($i['route'], $i));
        });

        $userId = auth()->id();
        $pinModels = $userId
            ? UserMenuPin::query()
                ->where('user_id', $userId)
                ->orderBy('position')
                ->orderBy('id')
                ->get()
            : collect();

        $pinnedRouteNames = $pinModels->pluck('route_name')->all();
        $pinnedLookup = $pinModels->keyBy('route_name');

        $pinnedItems = collect($pinnedRouteNames)
            ->filter(fn ($route) => $allRoutes->has($route))
            ->map(function ($route) use ($allRoutes, $pinnedLookup) {
                $base = $allRoutes->get($route);
                $base['pin_id'] = $pinnedLookup[$route]->id;

                return $base;
            })
            ->values();

        if ($q !== '') {
            $platformItems = $platformItems->filter(
                fn ($i) => str_contains(strtolower($i['label']), $q)
            );

            $groups = $groups
                ->map(fn ($items) => $items->filter(
                    fn ($item) => str_contains(strtolower($item['label']), $q)
                        || str_contains(strtolower($item['group']), $q)
                ))
                ->filter(fn ($items) => $items->isNotEmpty());

            $pinnedItems = $pinnedItems->filter(
                fn ($i) => str_contains(strtolower($i['label']), $q)
            )->values();
        }

        return view('livewire.sidebar-menu', [
            'groups' => $groups,
            'platformItems' => $platformItems,
            'pinnedItems' => $pinnedItems,
            'pinnedRouteNames' => $pinnedRouteNames,
            'currentRoute' => request()->route()?->getName(),
        ]);
    }
}
