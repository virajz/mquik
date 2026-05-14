<?php
?>
<div>
    <flux:modal name="smart-salary-form" class="md:w-lg">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit KPI Definition' : 'New KPI Definition' }}</flux:heading>
                <flux:subheading>Define a key the Smart Salary engine will score in Week 7. No formulas yet — just the registry.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="key"
                        label="Key"
                        placeholder="ATTENDANCE_PCT"
                        description="UPPER_SNAKE_CASE identifier"
                        class:input="font-mono uppercase tracking-wide"
                        required
                    />
                    <flux:input
                        wire:model="name"
                        label="Display name"
                        placeholder="Attendance %"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="category" variant="listbox" label="Category" required>
                        @foreach (\App\Modules\SmartSalary\Models\SmartSalary::categories() as $c)
                            <flux:select.option :value="$c">{{ $c }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="direction" variant="listbox" label="Direction" required>
                        @foreach (\App\Modules\SmartSalary\Models\SmartSalary::directions() as $k => $v)
                            <flux:select.option :value="$k">{{ $v }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="unit" label="Unit" placeholder="%, count, ₹, days" clearable />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="weight" type="number" step="0.01" min="0" label="Weight" description="Max contribution to total score" class:input="text-right font-mono" required />
                    <flux:input wire:model="sort_order" type="number" min="0" label="Sort order" class:input="text-right font-mono" />
                </div>

                <flux:textarea
                    wire:model="formula"
                    label="Formula"
                    placeholder="e.g. (days_present / total_working_days) * 100  — Week 7 wires this up"
                    rows="3"
                />

                <flux:textarea
                    wire:model="description"
                    label="Description"
                    placeholder="Any helpful note for HR (optional)"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" description="Drives whether this KPI participates in scoring." />
            </div>

            <flux:separator variant="subtle" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Add KPI' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
