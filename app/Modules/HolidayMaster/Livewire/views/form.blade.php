<div>
    <flux:modal name="holiday-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Holiday' : 'New Holiday' }}
                </flux:heading>
                <flux:subheading>
                    Workshop holiday calendar - weekly-off, national, festival & company holidays.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Name"
                            placeholder="e.g. DIWALI"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="DIW"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-center">
                    <flux:select wire:model="holiday_type" variant="listbox" label="Type">
                        @foreach (\App\Modules\HolidayMaster\Models\HolidayMaster::holidayTypes() as $k => $l)
                            <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:date-picker
                        wire:model="holiday_date"
                        label="Date"
                        placeholder="Pick a date"
                        description="Blank for weekly recurring"
                        with-today
                        selectable-header
                        fixed-weeks
                        clearable
                        type="input"
                    />
                    <flux:switch wire:model="is_recurring" label="Recurring" description="e.g. every Sunday." />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this holiday"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive holidays won't appear in dropdowns."
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
