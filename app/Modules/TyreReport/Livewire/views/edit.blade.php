<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('tyre-report.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Tyre Reports
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? 'Tyre Report '.$report_no : 'New Tyre Report' }}
                </flux:heading>
            </div>
        </div>

        <flux:separator />

        {{-- DETAILS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Details</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who and which vehicle, and when it was inspected.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model.live="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Search by name or phone…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Type a name or phone…" />
                        </x-slot>
                        @forelse ($this->customers as $c)
                            <flux:select.option :value="$c->id" wire:key="cust-{{ $c->id }}">{{ $c->name }}{{ $c->phone ? ' · '.$c->phone : '' }}</flux:select.option>
                        @empty
                            <flux:select.option value="" disabled>No matching customers.</flux:select.option>
                        @endforelse
                    </flux:select>

                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Search by reg no…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Type a registration…" />
                        </x-slot>
                        @forelse ($this->vehicles as $v)
                            <flux:select.option :value="$v->id" wire:key="veh-{{ $v->id }}">
                                {{ $v->registration_no }}{{ $v->model ? ' — '.trim(($v->model->brand?->name ?? '').' '.$v->model->name) : '' }}
                            </flux:select.option>
                        @empty
                            <flux:select.option value="" disabled>No matching vehicles.</flux:select.option>
                        @endforelse
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="inspected_by_id" variant="listbox" searchable clearable label="Inspected By" placeholder="Technician…">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="emp-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:field>
                        <flux:label>Odometer</flux:label>
                        <flux:input.group>
                            <flux:input wire:model="odometer_km" type="number" min="0" placeholder="45000" class:input="text-right font-mono" />
                            <flux:input.group.suffix>km</flux:input.group.suffix>
                        </flux:input.group>
                        <flux:error name="odometer_km" />
                    </flux:field>

                    <flux:date-picker wire:model="reported_on" label="Report Date" required with-today selectable-header fixed-weeks type="input" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- WHEELS --}}
        <section class="py-8 space-y-4">
            <div>
                <flux:heading size="lg">Wheels</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Measurements and condition for each of the five positions.</flux:text>
            </div>

            @foreach ($lines as $i => $line)
                <div wire:key="tyre-{{ $line['position'] }}" class="rounded-md border border-zinc-200 dark:border-zinc-800 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">{{ \App\Modules\TyreReport\Models\TyreReport::POSITIONS[$line['position']] }}</flux:heading>
                        <flux:select wire:model="lines.{{ $i }}.condition" variant="listbox" size="sm" class="w-40">
                            @foreach (\App\Modules\TyreReport\Models\TyreReport::conditions() as $key => $label)
                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <flux:input wire:model="lines.{{ $i }}.tyre_size" size="sm" label="Size" placeholder="205/45 R16" class:input="font-mono" />
                        <flux:input wire:model="lines.{{ $i }}.tyre_make" size="sm" label="Make" placeholder="MICHELIN" />
                        <flux:input wire:model="lines.{{ $i }}.pattern" size="sm" label="Pattern" placeholder="PRIMACY" />
                        <flux:input wire:model="lines.{{ $i }}.mfg_week_year" size="sm" label="Mfg (WWYY)" placeholder="2416" class:input="font-mono" />
                        <flux:field>
                            <flux:label size="sm">Pressure</flux:label>
                            <flux:input.group>
                                <flux:input wire:model="lines.{{ $i }}.pressure_psi" type="number" step="0.1" size="sm" placeholder="32" class:input="text-right font-mono" />
                                <flux:input.group.suffix>psi</flux:input.group.suffix>
                            </flux:input.group>
                        </flux:field>
                        <flux:field>
                            <flux:label size="sm">Tread</flux:label>
                            <flux:input.group>
                                <flux:input wire:model="lines.{{ $i }}.tread_depth_mm" type="number" step="0.1" size="sm" placeholder="6.5" class:input="text-right font-mono" />
                                <flux:input.group.suffix>mm</flux:input.group.suffix>
                            </flux:input.group>
                        </flux:field>
                        <flux:input wire:model="lines.{{ $i }}.notes" size="sm" label="Note" placeholder="Optional" class="md:col-span-2" />
                    </div>

                    <div class="flex flex-wrap gap-4 pt-1">
                        <flux:checkbox wire:model="lines.{{ $i }}.has_crack" label="Crack" />
                        <flux:checkbox wire:model="lines.{{ $i }}.has_bulge" label="Bulge" />
                        <flux:checkbox wire:model="lines.{{ $i }}.is_worn_out" label="Worn out" />
                        <flux:checkbox wire:model="lines.{{ $i }}.has_puncture" label="Puncture" />
                    </div>
                </div>
            @endforeach
        </section>

        <flux:separator />

        {{-- RECOMMENDATION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Recommendation</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What the advisor should suggest to the customer.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea wire:model="recommendation" label="Recommendation" placeholder="e.g. Replace both front tyres; rotate rears." rows="2" />
                <flux:textarea wire:model="notes" label="Internal Notes" rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('tyre-report.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create report' }}</flux:button>
        </div>
    </form>
</div>
