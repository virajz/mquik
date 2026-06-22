@use(App\Modules\TechnicianFinding\Models\TechnicianFinding)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('technician-finding.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Technician Findings
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? 'Finding '.$finding_no : 'New Technician Finding' }}
                </flux:heading>
            </div>
            @if ($editingId)
                <flux:badge :color="match ($status) {
                    'recommended' => 'amber', 'approved' => 'lime', 'rejected' => 'red', 'converted' => 'blue', default => 'zinc',
                }" size="lg">{{ TechnicianFinding::statuses()[$status] }}</flux:badge>
            @endif
        </div>

        <flux:separator class="mb-6" />

        <div class="space-y-5">
            <flux:select wire:model.live="job_card_id" variant="listbox" searchable label="Job Card" placeholder="Pick a job card…" required>
                @foreach ($this->jobCards as $jc)
                    <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">
                        {{ $jc->job_card_no }} — {{ trim($jc->customer?->first_name.' '.($jc->customer?->last_name ?? '')) }}
                        @if ($jc->customerVehicle) · {{ $jc->customerVehicle->registration_no }} @endif
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:select wire:model="vehicle_inspection_order_id" variant="listbox" searchable clearable label="Work Order (VIO)" placeholder="Optional…">
                    @foreach ($this->orders as $o)
                        <flux:select.option :value="$o->id" wire:key="vio-{{ $o->id }}">{{ $o->order_no }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="reported_by_id" variant="listbox" searchable clearable label="Reported By" placeholder="Technician…">
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="emp-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model="description" label="Finding" placeholder="e.g. BRAKE DISC WORN BELOW LIMIT" required />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:radio.group wire:model.live="finding_type" label="Type" variant="segmented">
                    @foreach (TechnicianFinding::types() as $key => $label)
                        <flux:radio :value="$key" :label="$label" />
                    @endforeach
                </flux:radio.group>
                <flux:select wire:model="recommendation" variant="listbox" label="Recommendation" required>
                    @foreach (TechnicianFinding::recommendations() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if ($finding_type === TechnicianFinding::TYPE_SPARE)
                <flux:select wire:model="spare_id" variant="listbox" searchable clearable label="Spare / Part" placeholder="Link a spare…">
                    @foreach ($this->spares as $s)
                        <flux:select.option :value="$s->id" wire:key="sp-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <flux:select wire:model="labour_id" variant="listbox" searchable clearable label="Labour" placeholder="Link a labour…">
                    @foreach ($this->labours as $l)
                        <flux:select.option :value="$l->id" wire:key="lb-{{ $l->id }}">{{ $l->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input wire:model="quantity" type="number" step="0.01" min="0" label="Quantity" placeholder="1" />
                <flux:input wire:model="estimated_amount" type="number" step="0.01" min="0" label="Est. Amount" prefix="₹" placeholder="0.00" />
                <flux:select wire:model="status" variant="listbox" label="Status" required>
                    @foreach (TechnicianFinding::statuses() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="notes" label="Notes" placeholder="Any extra context for the advisor / approver." rows="2" />
        </div>

        <flux:separator class="mt-6" />
        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('technician-finding.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Record Finding' }}</flux:button>
        </div>
    </form>
</div>
