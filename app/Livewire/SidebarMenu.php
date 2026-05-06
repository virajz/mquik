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

    public function movePin(int $id, string $direction): void
    {
        if (! $userId = auth()->id()) {
            return;
        }

        $pins = UserMenuPin::query()
            ->where('user_id', $userId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $index = $pins->search(fn ($p) => $p->id === $id);
        if ($index === false) {
            return;
        }

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;
        if (! isset($pins[$swapWith])) {
            return;
        }

        [$a, $b] = [$pins[$index], $pins[$swapWith]];
        // Use a sentinel value to dodge the unique (user_id, position) constraint during swap.
        $temp = -1;
        $aPos = $a->position;
        $bPos = $b->position;
        $a->update(['position' => $temp]);
        $b->update(['position' => $aPos]);
        $a->update(['position' => $bPos]);
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
