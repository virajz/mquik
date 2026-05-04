@props(['items' => []])

<flux:breadcrumbs>
    <flux:breadcrumbs.item :href="route('dashboard')" icon="home" />

    @foreach ($items as $item)
        @if (! empty($item['href']) && ! $loop->last)
            <flux:breadcrumbs.item :href="$item['href']">
                {{ $item['label'] }}
            </flux:breadcrumbs.item>
        @else
            <flux:breadcrumbs.item>
                {{ $item['label'] }}
            </flux:breadcrumbs.item>
        @endif
    @endforeach
</flux:breadcrumbs>
