<?php
?>
<div>
    <flux:modal name="leave-management-form" :dismissible="false" class="md:w-lg">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Leave Request' : 'New Leave Request' }}</flux:heading>
                <flux:subheading>Capture leave type, dates, and approval state.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:select wire:model="employee_id" variant="listbox" searchable label="Employee" placeholder="Pick an employee…" required autofocus>
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="emp-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="leave_type" variant="listbox" label="Leave Type" required>
                    @foreach (\App\Modules\LeaveManagement\Models\LeaveManagement::leaveTypes() as $k => $v)
                        <flux:select.option :value="$k">{{ $v }} ({{ $k }})</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:date-picker wire:model="from_date" label="From" with-today selectable-header fixed-weeks type="input" required />
                    <flux:date-picker wire:model="to_date" label="To" with-today selectable-header fixed-weeks type="input" required />
                </div>

                <flux:input wire:model="days_count" type="number" step="0.5" min="0.5" label="Days" placeholder="1.0" class:input="text-right font-mono" required />

                <flux:textarea wire:model="reason" label="Reason" placeholder="Optional reason from the employee" rows="2" />

                <flux:separator variant="subtle" />

                <flux:select wire:model="status" variant="listbox" label="Status" required>
                    @foreach (\App\Modules\LeaveManagement\Models\LeaveManagement::statuses() as $k => $v)
                        <flux:select.option :value="$k">{{ $v }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="approved_by_employee_id" variant="listbox" searchable clearable label="Approver" placeholder="Pick an approver">
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="appr-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="decision_notes" label="Decision notes" placeholder="Why approved or rejected (optional)" rows="2" />
            </div>

            <flux:separator variant="subtle" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
