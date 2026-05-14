<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-900 antialiased">

    {{-- Primary sidebar — fully collapsible (desktop + mobile) --}}
    <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">

        <flux:sidebar.header>
            <flux:sidebar.brand :href="route('dashboard')" logo="/mquik.png" logo:dark="/mquik.png" wire:navigate />
            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <livewire:sidebar-menu />

        <flux:sidebar.spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" wire:navigate>
                {{ __('Settings') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    {{-- Secondary header — section navbar (groups) on desktop, sidebar toggle on mobile --}}
    @php
        $menu = app(\App\Support\Menu::class);
        $sectionTabs = $menu->sectionTabs();
        $activeGroup = $menu->activeGroup();
    @endphp

    <flux:header class="block! border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">

        {{-- Mobile bar --}}
        <flux:navbar class="lg:hidden w-full">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <flux:dropdown position="bottom" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:navbar>

        {{-- Desktop section navbar — one tab per menu group --}}
        <flux:navbar scrollable class="max-lg:hidden">
            <flux:navbar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                wire:navigate>
                {{ __('Dashboard') }}
            </flux:navbar.item>

            @foreach ($sectionTabs as $tab)
                <flux:navbar.item :href="$tab['route'] ? route($tab['route']) : '#'"
                    :current="$activeGroup === $tab['group']" wire:navigate>
                    {{ __($tab['label']) }}
                </flux:navbar.item>
            @endforeach

            <flux:spacer />

            <flux:modal.trigger name="master-search" shortcut="cmd.k">
                <button type="button"
                    class="hidden lg:flex items-center gap-2 px-3 py-1.5 text-sm rounded-md border border-zinc-200 bg-white text-zinc-500 hover:text-zinc-900 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100"
                >
                    <flux:icon.magnifying-glass class="size-4" />
                    <span>Search...</span>
                    <kbd class="ml-2 px-1.5 py-0.5 text-xs font-mono bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded">⌘K</kbd>
                </button>
            </flux:modal.trigger>
        </flux:navbar>
    </flux:header>

    <livewire:master-search />

    <flux:main class="px-6 py-6 lg:px-8 lg:py-8">
        {{ $slot }}
    </flux:main>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts

    {{-- Sidebar: scroll the active menu item into view on first load and after every wire:navigate.
         Flux marks the active item with `data-current` (set by button-or-link.blade.php's
         attribute merge); we wait one animation frame so any post-navigation morph completes
         before measuring positions. --}}
    <script>
        (function () {
            const scrollActiveSidebarItem = () => {
                requestAnimationFrame(() => {
                    const el = document.querySelector('[data-flux-sidebar] [data-flux-sidebar-item][data-current]');
                    if (el) el.scrollIntoView({ block: 'center', behavior: 'auto' });
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', scrollActiveSidebarItem);
            } else {
                scrollActiveSidebarItem();
            }
            document.addEventListener('livewire:navigated', scrollActiveSidebarItem);
        })();
    </script>
</body>

</html>
