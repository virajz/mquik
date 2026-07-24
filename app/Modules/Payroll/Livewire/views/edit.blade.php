<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('payroll.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Payroll
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Edit Payroll Entry' : 'New Payroll Entry' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Manual payroll components for a single employee + month.</flux:text>
        </div>

        <flux:separator />

        {{-- EMPLOYEE & PERIOD --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Employee & Period</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">One entry per employee per month.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:select wire:model="employee_id" variant="listbox" searchable label="Employee" placeholder="Pick an employee…" required autofocus>
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="emp-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="period_month" variant="listbox" label="Month" required>
                        @foreach (\App\Modules\Payroll\Models\Payroll::months() as $num => $name)
                            <flux:select.option :value="$num">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="period_year" type="number" min="2020" max="2100" label="Year" class:input="font-mono" required />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- COMPONENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Components</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Gross and net update as you type.</flux:text>
            </div>
            {{-- Gross/Net preview is pure arithmetic, computed in Alpine so it updates
                 instantly; the saved gross/net are still recomputed server-side in save(). --}}
            <div class="space-y-4 min-w-0" x-data="{
                n(v) { return parseFloat(v) || 0 },
                get gross() { return this.n($wire.basic_amount) + this.n($wire.hra_amount) + this.n($wire.da_amount) + this.n($wire.allowances_amount) + this.n($wire.incentive_amount) },
                get net() { return this.gross - this.n($wire.deductions_amount) },
                money(v) { return '₹ ' + v.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
            }">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="basic_amount" type="number" step="0.01" min="0" label="Basic" class:input="text-right font-mono" required />
                    <flux:input wire:model="hra_amount" type="number" step="0.01" min="0" label="HRA" class:input="text-right font-mono" required />
                    <flux:input wire:model="da_amount" type="number" step="0.01" min="0" label="DA" class:input="text-right font-mono" required />
                    <flux:input wire:model="allowances_amount" type="number" step="0.01" min="0" label="Allowances (OT + Bonus)" class:input="text-right font-mono" required />
                    <flux:input wire:model="incentive_amount" type="number" step="0.01" min="0" label="Incentive (Smart Salary)" class:input="text-right font-mono" required />
                    <flux:input wire:model="deductions_amount" type="number" step="0.01" min="0" label="Deductions (PF/ESI/Adv)" class:input="text-right font-mono" required />
                </div>

                <div class="rounded-md bg-zinc-50 dark:bg-zinc-900 px-4 py-3 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-zinc-500 text-xs">Gross</div>
                        <div class="font-mono font-medium" x-text="money(gross)"></div>
                    </div>
                    <div>
                        <div class="text-zinc-500 text-xs">Net</div>
                        <div class="font-mono font-medium" x-text="money(net)"></div>
                    </div>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status & Notes</flux:heading>
            </div>
            <div class="space-y-4 min-w-0">
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
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('payroll.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create entry' }}</flux:button>
        </div>
    </form>
</div>
