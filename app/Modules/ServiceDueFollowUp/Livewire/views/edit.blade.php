@php($SDF = \App\Modules\ServiceDueFollowUp\Models\ServiceDueFollowUp::class)
@php($ATT = \App\Modules\ServiceDueFollowUp\Models\ServiceDueFollowUpAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('service-due-follow-up.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Service Due Follow-Ups
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($follow_up_no ?: 'Edit Follow-Up') : 'New Service Due Follow-Up' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Track an upcoming scheduled-service follow-up.</flux:text>
        </div>

        <flux:separator />

        {{-- DUE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Service Due</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Vehicle, due date and interval.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Customer…" autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Search customer…" /></x-slot>
                        @foreach ($this->customers as $c)<flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ trim($c->first_name.' '.$c->last_name) }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                        @foreach ($this->vehicles as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_by_id" variant="listbox" searchable clearable label="Follow-up By" placeholder="Advisor / CRM…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="fb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model.live="service_interval_method" variant="listbox" clearable label="Interval Method" placeholder="Km / Date…">
                        @foreach ($SDF::intervalMethods() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="service_interval" label="Service Interval" placeholder="10000 / 6_month" class:input="font-mono" />
                    <flux:date-picker wire:model="due_date" label="Due Date" with-today selectable-header fixed-weeks type="input" />
                    <flux:input wire:model="odometer" type="number" min="0" label="Odometer" class:input="text-right font-mono" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="service_history_reference" label="Service History Ref" placeholder="History ref" class:input="font-mono" />
                    <flux:select wire:model="reminder_frequency" variant="listbox" clearable label="Reminder Frequency" placeholder="Config only — never sent">
                        @foreach ($SDF::reminderFrequencies() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- FOLLOW-UP --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Follow-up</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Attempt, response and status.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="follow_up_attempt" variant="listbox" clearable label="Attempt" placeholder="1st / 2nd…">
                        @foreach ($SDF::followUpAttempts() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode" variant="listbox" clearable label="Mode" placeholder="WhatsApp / Call…">
                        @foreach ($SDF::followUpModes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_response" variant="listbox" clearable label="Customer Response" placeholder="What they said…">
                        @foreach ($SDF::customerResponses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                    <flux:select wire:model.live="status" variant="listbox" label="Follow-up Status" required>
                        @foreach ($SDF::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.status === 'appointment_booked'" x-cloak>
                        <div class="grid grid-cols-2 gap-2">
                            <flux:date-picker wire:model="appointment_at" label="Appointment At" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                            <flux:time-picker wire:model="appointment_at_time" label="Time" />
                        </div>
                    </div>
                </div>
                <div x-show="$wire.status === 'lost_opportunity'" x-cloak>
                    <flux:select wire:model="lost_reason" variant="listbox" clearable label="Lost / Dissatisfied Reason" placeholder="Why lost…" class="md:max-w-sm">
                        @foreach ($SDF::lostReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="lost_reason" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="customer_satisfaction" variant="listbox" clearable label="Customer Satisfaction" placeholder="Satisfied / Dissatisfied">
                        @foreach ($SDF::satisfactions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_retention" variant="listbox" clearable label="Customer Retention" placeholder="Active / Lost / Recovered">
                        @foreach ($SDF::retentions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ESCALATION & RECOMMENDATION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Escalation & Recommendation</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="escalation" variant="listbox" clearable label="Escalation" placeholder="If escalated…">
                        @foreach ($SDF::escalations() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.escalation" x-cloak>
                        <flux:select wire:model="escalation_reason" variant="listbox" clearable label="Escalation Reason" placeholder="Why escalated…">
                            @foreach ($SDF::escalationReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="escalation_reason" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="estimate_template_reference" label="Estimate Template Ref" placeholder="Template" class:input="font-mono" />
                    <flux:input wire:model="price_list_reference" label="Price List Ref" placeholder="Price list" class:input="font-mono" />
                    <flux:input wire:model="recommended_service_reference" label="Recommended Service (AMC / Combo)" placeholder="Package ref" />
                </div>

                {{-- Attachments --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Attachments</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                    </div>
                    @forelse ($attachments as $i => $att)
                        <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end mb-2 p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Type…">
                                @foreach ($ATT::attachmentTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <div>
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                                @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Customer note / follow-up notes.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Follow-up remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('service-due-follow-up.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create follow-up' }}</flux:button>
        </div>
    </form>
</div>
