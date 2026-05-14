<div>
    <flux:modal name="service-package-master-form" class="md:w-3xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Package' : 'New Service Package' }}</flux:heading>
                <flux:subheading>Combo or AMC bundle of services with validity windows.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-[1fr_180px] gap-3">
                    <flux:input
                        wire:model="name"
                        label="Package Name"
                        placeholder="STANDARD AMC PACK"
                        required
                        autofocus
                    />
                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="SP-001"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <flux:textarea
                    wire:model="description"
                    label="Description"
                    placeholder="What this package covers — services, parts, validity, perks."
                    rows="2"
                />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input
                        wire:model="validity_months"
                        type="number"
                        min="1"
                        max="120"
                        label="Validity (months)"
                        placeholder="12"
                    />
                    <flux:input
                        wire:model="validity_km"
                        type="number"
                        min="0"
                        label="Validity (km)"
                        placeholder="10000"
                    />
                    <flux:input.group label="Total Price">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input
                            wire:model="total_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class:input="text-right font-mono"
                        />
                    </flux:input.group>
                </div>

                <flux:separator variant="subtle" />

                {{-- Line items: services included in this package --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <flux:heading size="sm">Included Services</flux:heading>
                            <flux:text size="sm" class="text-zinc-500 mt-0.5">Each row is one scheduled service in the package.</flux:text>
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addService">Add service</flux:button>
                    </div>

                    @if (count($services) === 0)
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            No services added yet. Click <span class="font-medium">Add service</span> to start.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($services as $i => $row)
                                <div wire:key="svc-row-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[40px_1fr_100px_120px_120px_40px] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                                    <flux:input
                                        wire:model="services.{{ $i }}.sequence_no"
                                        type="number"
                                        min="1"
                                        max="99"
                                        size="sm"
                                        label="#"
                                        class:input="text-center font-mono"
                                    />
                                    <flux:select
                                        wire:model="services.{{ $i }}.service_type_id"
                                        variant="listbox"
                                        searchable
                                        size="sm"
                                        label="Service Type"
                                        placeholder="Pick a service type…"
                                    >
                                        @foreach ($this->serviceTypes as $st)
                                            <flux:select.option :value="$st->id" wire:key="svc-{{ $i }}-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:input
                                        wire:model="services.{{ $i }}.due_after_months"
                                        type="number"
                                        min="0"
                                        max="120"
                                        size="sm"
                                        label="After (mo)"
                                        placeholder="—"
                                    />
                                    <flux:input
                                        wire:model="services.{{ $i }}.due_after_km"
                                        type="number"
                                        min="0"
                                        size="sm"
                                        label="After (km)"
                                        placeholder="—"
                                    />
                                    <flux:input
                                        wire:model="services.{{ $i }}.notes"
                                        size="sm"
                                        label="Notes"
                                        placeholder="—"
                                    />
                                    <flux:button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        icon="x-mark"
                                        wire:click="removeService({{ $i }})"
                                        class="h-9!"
                                    />
                                </div>
                                <flux:error name="services.{{ $i }}.service_type_id" />
                            @endforeach
                        </div>
                    @endif
                </div>

                <flux:separator variant="subtle" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:switch wire:model="is_amc" label="Is AMC Package" description="AMCs trigger AMC-specific reminders; combo packs don't." />
                    <flux:switch wire:model="is_active" label="Active" description="Inactive packages are hidden from job-card pickers." />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
