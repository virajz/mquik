<?php
?>
<div>
    <flux:modal name="payroll-form" :dismissible="false" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Payroll Entry' : 'New Payroll Entry' }}</flux:heading>
                <flux:subheading>Manual payroll components for a single employee + month.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:select wire:model="employee_id" variant="listbox" searchable label="Employee" placeholder="Pick an employee…" required autofocus>
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="emp-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model.live="period_month" variant="listbox" label="Month" required>
                        @foreach (\App\Modules\Payroll\Models\Payroll::months() as $num => $name)
                            <flux:select.option :value="$num">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model.live="period_year" type="number" min="2020" max="2100" label="Year" class:input="font-mono" required />
                </div>

                <flux:separator variant="subtle" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model.live="basic_amount" type="number" step="0.01" min="0" label="Basic" class:input="text-right font-mono" required />
                    <flux:input wire:model.live="hra_amount" type="number" step="0.01" min="0" label="HRA" class:input="text-right font-mono" required />
                    <flux:input wire:model.live="da_amount" type="number" step="0.01" min="0" label="DA" class:input="text-right font-mono" required />
                    <flux:input wire:model.live="allowances_amount" type="number" step="0.01" min="0" label="Allowances (OT + Bonus)" class:input="text-right font-mono" required />
                    <flux:input wire:model.live="incentive_amount" type="number" step="0.01" min="0" label="Incentive (Smart Salary)" class:input="text-right font-mono" required />
                    <flux:input wire:model.live="deductions_amount" type="number" step="0.01" min="0" label="Deductions (PF/ESI/Adv)" class:input="text-right font-mono" required />
                </div>

                <div class="rounded-md bg-zinc-50 dark:bg-zinc-900 px-4 py-3 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-zinc-500 text-xs">Gross</div>
                        <div class="font-mono font-medium">₹ {{ number_format($this->grossPreview, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-zinc-500 text-xs">Net</div>
                        <div class="font-mono font-medium">₹ {{ number_format($this->netPreview, 2) }}</div>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="status" variant="listbox" label="Status" required>
                        @foreach (\App\Modules\Payroll\Models\Payroll::statuses() as $k => $v)
                            <flux:select.option :value="$k">{{ $v }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:date-picker wire:model="payment_date" label="Payment Date" with-today selectable-header fixed-weeks type="input" clearable />
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Optional notes" rows="2" />
            </div>

            <flux:separator variant="subtle" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
