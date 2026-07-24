<div>
    <flux:modal name="hsn-master-form" :dismissible="false" class="md:w-lg">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit HSN / SAC Code' : 'New HSN / SAC Code' }}
                </flux:heading>
                <flux:subheading>
                    GST classification codes &mdash; HSN for goods, SAC for services, with the default rate.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="8708"
                        maxlength="8"
                        inputmode="numeric"
                        required
                        autofocus
                        class:input="font-mono tracking-wide"
                    />

                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Description"
                            placeholder="e.g. PARTS AND ACCESSORIES OF MOTOR VEHICLES"
                            required
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="kind" variant="listbox" label="Kind" required>
                        @foreach (\App\Modules\HsnMaster\Models\HsnMaster::kinds() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:field>
                        <flux:label>Default GST %</flux:label>
                        <flux:input.group>
                            <flux:input
                                wire:model="gst_percent"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                placeholder="18"
                                class:input="text-right font-mono"
                            />
                            <flux:input.group.suffix>%</flux:input.group.suffix>
                        </flux:input.group>
                        <flux:description>Indicative only &mdash; the tax slab on the line still governs.</flux:description>
                        <flux:error name="gst_percent" />
                    </flux:field>
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this code"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive codes won&rsquo;t appear in spare or labour dropdowns."
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $editingId ? 'Save changes' : 'Create' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
