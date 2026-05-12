<div>
    <flux:modal name="service-type-master-form" class="md:w-lg">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Service Type' : 'New Service Type' }}</flux:heading>
                <flux:subheading>How the workshop categorises a job — drives advisor routing, packages, and reports.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Service Type"
                            placeholder="e.g. PERIODIC MAINTENANCE"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="PMS"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <flux:select wire:model="workshop_department_id" label="Workshop Department" variant="combobox" required>
                    <x-slot name="input">
                        <flux:select.input wire:model="workshopDepartmentSearch" placeholder="Pick or type to add…" />
                    </x-slot>
                    @foreach ($departments as $d)
                        <flux:select.option :value="$d->id" wire:key="wd-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                    @endforeach
                    @can('workshop_department_master.create')
                        <flux:select.option.create wire:click="createWorkshopDepartment" min-length="2">
                            Create "<span wire:text="workshopDepartmentSearch"></span>"
                        </flux:select.option.create>
                    @endcan
                </flux:select>

                <flux:textarea wire:model="notes" label="Notes" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="requires_advisor" label="Requires advisor" description="Job cards of this type must be assigned to an advisor before opening." />

                <flux:switch wire:model="is_active" label="Active" description="Inactive service types won't appear in appointment / job card dropdowns." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
