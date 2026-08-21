<div>
    <flux:modal name="requested-repair-master-form" :dismissible="false" class="md:w-lg">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Requested Repair' : 'New Requested Repair' }}
                </flux:heading>
                <flux:subheading>
                    Common miscellaneous repairs a customer can request on a job card.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Type Name"
                            placeholder="e.g. WHEEL ALIGNMENT"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="ENG"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                {{-- Which departments offer this repair. Left empty it stays
                     available everywhere, which is how existing rows behave. --}}
                <flux:select wire:model="workshopDepartmentIds" variant="listbox" multiple searchable clearable
                    label="Departments" placeholder="All departments"
                    description="Only these departments offer this repair on a job card. Leave blank to show it in all.">
                    @foreach ($this->workshopDepartments as $d)
                        <flux:select.option :value="$d->id" wire:key="wd-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this requested repair"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive items won't appear in the job card requested-repairs picker."
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
