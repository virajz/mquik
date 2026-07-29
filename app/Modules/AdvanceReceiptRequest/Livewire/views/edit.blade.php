@php($ARR = \App\Modules\AdvanceReceiptRequest\Models\AdvanceReceiptRequest::class)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('advance-receipt-request.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Advance Receipt Request
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($request_no ?: 'Edit Request') : 'New Advance Request' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Request an advance payment from the customer.</flux:text>
        </div>

        <flux:separator />

        {{-- CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Context</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Job card, estimate, customer.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Link a job card…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="sales_estimate_id" variant="listbox" searchable clearable :filter="false" label="Estimate" placeholder="Link an estimate…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="estimateSearch" placeholder="Search estimate…" /></x-slot>
                        @foreach ($this->estimates as $es)
                            <flux:select.option :value="$es->id" wire:key="es-{{ $es->id }}">{{ $es->estimate_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Search customer…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Name or phone…" /></x-slot>
                        @foreach ($this->customers as $c)
                            <flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration no…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search reg no…" /></x-slot>
                        @foreach ($this->vehicles as $veh)
                            <flux:select.option :value="$veh->id" wire:key="vh-{{ $veh->id }}">{{ $veh->registration_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" searchable clearable label="Department" placeholder="Optional">
                        @foreach ($this->departments as $d)
                            <flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="service_type_id" variant="listbox" searchable clearable label="Service Type" placeholder="Optional">
                        @foreach ($this->serviceTypes as $st)
                            <flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="employee_id" variant="listbox" searchable clearable label="Advisor / Cashier" placeholder="Optional">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="em-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ADVANCE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Advance</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Purpose and how the amount is derived.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="advance_purpose" variant="listbox" clearable label="Advance Purpose" placeholder="Purpose…">
                        @foreach ($ARR::advancePurposes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="amount_type" variant="listbox" clearable label="Amount Type" placeholder="How derived…">
                        @foreach ($ARR::amountTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div x-show="$wire.amount_type === 'percent_of_estimate'" x-cloak>
                        <flux:input wire:model="percent" type="number" step="0.01" min="0" max="100" label="Percent of Estimate" class:input="text-right font-mono" />
                        <flux:error name="percent" />
                    </div>
                    <flux:input.group label="Requested Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- STATUS & REMINDERS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status & Reminders</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Reminders are configured only — nothing is sent.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="payment_status" variant="listbox" label="Payment Status" required>
                        @foreach ($ARR::paymentStatuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.payment_status === 'rejected'" x-cloak>
                        <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason" placeholder="Why rejected">
                            @foreach ($ARR::rejectionReasons() as $key => $label)
                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="rejection_reason" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model.live="reminder_time" variant="listbox" clearable label="Auto Reminder" placeholder="Off">
                        @foreach ($ARR::reminderTimes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.reminder_time === 'custom'" x-cloak>
                        <flux:input wire:model="reminder_custom_time" type="time" label="Custom Time" />
                        <flux:error name="reminder_custom_time" />
                    </div>
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / SMS / …">
                        @foreach ($this->followUpModes as $fm)
                            <flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:textarea wire:model="notes" label="Notes" placeholder="Optional remarks." rows="2" />
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Attachments</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Supporting documents — PDF or image.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                </div>
                @forelse ($attachments as $i => $att)
                    <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div>
                            <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" label="File" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                            @if (! empty($att['path']))
                                <flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>
                            @endif
                            <flux:error name="attachmentFiles.{{ $i }}" />
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No files yet. Click <span class="font-medium">Add file</span> to attach a document.
                    </div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('advance-receipt-request.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create request' }}</flux:button>
        </div>
    </form>
</div>
