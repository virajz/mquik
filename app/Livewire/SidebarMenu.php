<?php

namespace App\Livewire;

use App\Support\Menu;
use Livewire\Component;

class SidebarMenu extends Component
{
    public string $search = '';

    public function clear(): void
    {
        $this->search = '';
    }

    public function render()
    {
        $q = strtolower(trim($this->search));

        $groups = app(Menu::class)->forCurrentUser();

        $platformItems = collect([
            ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard'],
        ]);

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
        }

        return view('livewire.sidebar-menu', [
            'groups' => $groups,
            'platformItems' => $platformItems,
            'currentRoute' => request()->route()?->getName(),
        ]);
    }
}
