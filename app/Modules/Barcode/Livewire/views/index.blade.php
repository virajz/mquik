<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Barcode Labels</flux:heading>
            <flux:text class="mt-1">Generate and manage barcode labels for spares.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('barcode.create')
                <flux:button variant="primary" icon="qr-code" wire:click="openGenerate">
                    Generate Label
                </flux:button>
            @endcan
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-4 flex items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search barcode, spare name or code…"
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Spare</flux:table.column>
            <flux:table.column class="w-56">Barcode</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'barcode_type'" :direction="$sortDirection" wire:click="sort('barcode_type')">
                Type
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'label_size'" :direction="$sortDirection" wire:click="sort('label_size')">
                Label Size
            </flux:table.column>
            <flux:table.column class="w-16 text-center">Copies</flux:table.column>
            <flux:table.column class="w-24">Primary</flux:table.column>
            <flux:table.column class="w-28" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id" wire:key="row-{{ $row->id }}">
                    <flux:table.cell>
                        <div class="font-medium">{{ $row->spare?->name ?? '—' }}</div>
                        @if ($row->spare?->spare_code)
                            <div class="font-mono text-xs text-zinc-500">{{ $row->spare->spare_code }}</div>
                        @endif
                    </flux:table.cell>

                    {{-- Barcode visual rendered by JsBarcode / qrcode --}}
                    <flux:table.cell>
                        @if ($row->barcode_type === 'qr')
                            <canvas
                                x-init="renderBarcode($el)"
                                data-barcode="QR"
                                data-value="{{ $row->barcode }}"
                                data-size="80"
                                wire:ignore
                            ></canvas>
                        @else
                            <svg
                                x-init="renderBarcode($el)"
                                data-barcode="{{ strtoupper($row->barcode_type) }}"
                                data-value="{{ $row->barcode }}"
                                data-height="40"
                                class="max-w-52"
                                wire:ignore
                            ></svg>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge color="zinc" size="sm">{{ $barcodeTypes[$row->barcode_type] ?? $row->barcode_type }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">
                        {{ $labelSizes[$row->label_size] ?? $row->label_size }}
                    </flux:table.cell>
                    <flux:table.cell class="text-center text-sm">
                        {{ $row->copies }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($row->is_primary)
                            <flux:badge color="green" size="sm">Primary</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            {{-- Print button --}}
                            <flux:modal.trigger :name="'bc-print-' . $row->id">
                                <flux:button size="sm" variant="ghost" icon="printer" title="Print label" />
                            </flux:modal.trigger>

                            @can('barcode.delete')
                                <flux:modal.trigger :name="'bc-del-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                            @endcan

                            {{-- Print modal --}}
                            <flux:modal :name="'bc-print-' . $row->id" class="md:w-md">
                                <div class="space-y-5">
                                    <flux:heading size="lg">Print Label</flux:heading>

                                    {{-- Label preview (only barcode content inside lbl-ID, no metadata) --}}
                                    <div
                                        id="lbl-{{ $row->id }}"
                                        class="flex flex-col items-center gap-2 rounded-lg border border-zinc-200 bg-white p-4"
                                    >
                                        <div class="text-center text-xs font-semibold uppercase tracking-wide text-zinc-700">
                                            {{ $row->spare?->name }}
                                        </div>

                                        @if ($row->barcode_type === 'qr')
                                            <canvas
                                                x-init="renderBarcode($el)"
                                                data-barcode="QR"
                                                data-value="{{ $row->barcode }}"
                                                data-size="140"
                                                wire:ignore
                                            ></canvas>
                                        @else
                                            <svg
                                                x-init="renderBarcode($el)"
                                                data-barcode="{{ strtoupper($row->barcode_type) }}"
                                                data-value="{{ $row->barcode }}"
                                                data-height="70"
                                                class="max-w-72"
                                                wire:ignore
                                            ></svg>
                                        @endif
                                    </div>

                                    {{-- Metadata shown only in the modal, not printed --}}
                                    <div class="rounded-lg bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
                                        <div class="flex items-center justify-between">
                                            <span>Label size</span>
                                            <span class="font-medium">{{ $labelSizes[$row->label_size] ?? $row->label_size }}</span>
                                        </div>
                                        <div class="mt-1 flex items-center justify-between">
                                            <span>Copies needed</span>
                                            <span class="font-semibold text-orange-600">{{ $row->copies }}</span>
                                        </div>
                                        @if ($row->copies > 1)
                                            <div class="mt-2 text-xs text-zinc-400">
                                                Set <strong>Copies = {{ $row->copies }}</strong> in the print dialog.
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Close</flux:button>
                                        </flux:modal.close>
                                        <flux:button
                                            variant="primary"
                                            icon="printer"
                                            :href="route('barcode.pdf', ['label' => $row->id])"
                                            target="_blank"
                                        >
                                            Print {{ $row->copies }} {{ $row->copies === 1 ? 'copy' : 'copies' }}
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:modal>

                            @can('barcode.delete')
                                <flux:modal :name="'bc-del-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete label?</flux:heading>
                                        <flux:text>Remove <span class="font-mono font-semibold">{{ $row->barcode }}</span> for {{ $row->spare?->name }}? Cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close>
                                                <flux:button variant="ghost">Cancel</flux:button>
                                            </flux:modal.close>
                                            <flux:button variant="danger"
                                                wire:click="delete({{ $row->id }})"
                                                x-on:click="$flux.modal('bc-del-{{ $row->id }}').close()">
                                                Delete
                                            </flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.qr-code class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No barcode labels yet</div>
                        <flux:text class="mt-1">Generate a label for a spare to get started.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())
        <div class="mt-4">
            <flux:pagination :paginator="$rows" />
        </div>
    @endif

    {{-- Generate modal --}}
    <flux:modal name="barcode-generate" class="md:w-lg">
        <form wire:submit="generate" class="space-y-5">
            <div>
                <flux:heading size="lg">Generate Barcode Label</flux:heading>
                <flux:subheading>Select a spare and configure the label.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:select wire:model="selectedSpareId" label="Spare" required :filter="false">
                <x-slot name="search">
                    <flux:select.search wire:model.live.debounce.250ms="spareSearch" placeholder="Type a part name or number…" />
                </x-slot>
                    <flux:select.option value="">— select spare —</flux:select.option>
                    @foreach ($this->spares as $spare)
                        <flux:select.option :value="$spare->id">
                            {{ $spare->name }}{{ $spare->spare_code ? ' ('.$spare->spare_code.')' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="barcodeType" label="Barcode Type">
                        @foreach ($barcodeTypes as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="labelSize" label="Label Size">
                        @foreach ($labelSizes as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="copies" label="Copies to Print" type="number" min="1" max="100" />

                <flux:checkbox wire:model="isPrimary" label="Set as primary barcode for this spare" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="qr-code">
                    Generate
                </flux:button>
            </div>
        </form>
    </flux:modal>

</div>
