<?php
?>
<div>
    <flux:modal name="late-memo-form" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Late Memo' : 'New Late Memo' }}</flux:heading>
                <flux:subheading>Issue a memo for an employee who came in late.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:select wire:model="employee_id" variant="listbox" searchable label="Employee" placeholder="Pick an employee…" required autofocus>
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="emp-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:date-picker wire:model="memo_date" label="Date" with-today selectable-header fixed-weeks type="input" required />

                <flux:input.group label="Late by">
                    <flux:input wire:model="late_by_minutes" type="number" min="0" max="720" placeholder="30" class:input="text-right font-mono" required />
                    <flux:input.group.suffix>minutes</flux:input.group.suffix>
                </flux:input.group>

                <flux:textarea wire:model="reason" label="Reason" placeholder="Optional reason from the employee" rows="2" />

                <flux:separator variant="subtle" />

                <flux:select wire:model="status" variant="listbox" label="Status" required>
                    @foreach (\App\Modules\LateMemo\Models\LateMemo::statuses() as $k => $v)
                        <flux:select.option :value="$k">{{ $v }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="issued_by_employee_id" variant="listbox" searchable clearable label="Issued by" placeholder="Pick who issued the memo">
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="iss-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="decision_notes" label="Notes" placeholder="Optional notes (e.g. why waived)" rows="2" />
            </div>

            <flux:separator variant="subtle" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Issue memo' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
