<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Page List</flux:heading>
            <flux:text class="mt-1">Every page registered in the system. Use this to audit permissions or onboard new staff.</flux:text>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by label, module, or route..."
            icon="magnifying-glass"
            clearable
            class="max-w-md"
        />
        <flux:select wire:model.live="groupFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All groups</flux:select.option>
            @foreach ($groups as $g)
                <flux:select.option :value="$g">{{ $g }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-48">Module</flux:table.column>
            <flux:table.column class="w-32">Group</flux:table.column>
            <flux:table.column class="w-12">Icon</flux:table.column>
            <flux:table.column>Label</flux:table.column>
            <flux:table.column>Route</flux:table.column>
            <flux:table.column>Permission</flux:table.column>
            <flux:table.column class="w-72">Badges</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row['module'] . '-' . ($row['route'] ?? 'none') . '-' . $loop->index">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row['module'] }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="zinc" size="sm">{{ $row['group'] }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:icon :name="$row['icon']" class="size-4 text-zinc-500" />
                    </flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $row['label'] }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        {{ $row['route'] ?? '' }}
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        {{ $row['permission'] ?? '' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap items-center gap-1">
                            @if ($row['is_searchable'])
                                <flux:badge color="lime" size="sm">Searchable</flux:badge>
                            @endif
                            @if ($row['is_exportable'])
                                <flux:badge color="zinc" size="sm">Exportable</flux:badge>
                            @endif
                            @if ($row['is_importable'])
                                <flux:badge color="zinc" size="sm">Importable</flux:badge>
                            @endif
                            @if (! $row['visible'])
                                <flux:badge color="red" size="sm">Hidden</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.document-text class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No pages match.</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
