<x-layouts::app.sidebar :title="$title ?? null">
    @isset($breadcrumbs)
        <x-slot:breadcrumbs>{{ $breadcrumbs }}</x-slot:breadcrumbs>
    @endisset

    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts::app.sidebar>
