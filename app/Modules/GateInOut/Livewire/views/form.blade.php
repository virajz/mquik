<div>
    <flux:modal name="gate-in-out-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Gate Event' : 'Record Gate Event' }}</flux:heading>
                <flux:subheading>Stamp a vehicle entering or leaving the workshop. Reg-no auto-resolves to a customer vehicle if matched.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="direction" variant="listbox" label="Direction" required>
                        @foreach (\App\Modules\GateInOut\Models\GateInOut::directions() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:date-picker wire:model="gated_date" label="Date" placeholder="Today" with-today selectable-header fixed-weeks type="input" />
                    <flux:time-picker wire:model="gated_time" label="Time" placeholder="Now" type="input" />
                </div>

                <flux:input
                    wire:model.live.debounce.500ms="registration_no"
                    label="Registration Number"
                    placeholder="GJ 05 AA 1234"
                    description="Type or paste the reg-no — we try to auto-link to a customer vehicle."
                    class:input="font-mono uppercase tracking-wider"
                    autofocus
                    required
                />

                @if ($customer_vehicle_id)
                    <div class="rounded-md border border-lime-200 dark:border-lime-900 bg-lime-50 dark:bg-lime-900/20 px-3 py-2 text-sm flex items-center gap-2">
                        <flux:icon.check-circle class="size-4 text-lime-600 dark:text-lime-400" />
                        <span>Linked to a known customer vehicle (#{{ $customer_vehicle_id }}).</span>
                    </div>
                @elseif (trim($registration_no))
                    <div class="rounded-md border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 text-sm flex items-center gap-2">
                        <flux:icon.exclamation-triangle class="size-4 text-amber-600 dark:text-amber-400" />
                        <span>No matching customer vehicle — recording as walk-in.</span>
                    </div>
                @endif

                <flux:select wire:model="source" variant="listbox" label="Source" class="md:max-w-xs">
                    <flux:select.option value="manual">Manual Entry</flux:select.option>
                    <flux:select.option value="anpr">ANPR Camera</flux:select.option>
                </flux:select>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Optional — e.g. 'driver waiting outside', 'late entry'." rows="2" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Record event' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
