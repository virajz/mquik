@php($SRF = \App\Modules\ServiceRecommendationFollowUp\Models\ServiceRecommendationFollowUp::class)
@php($ATT = \App\Modules\ServiceRecommendationFollowUp\Models\ServiceRecommendationFollowUpAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('service-recommendation-follow-up.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Service Recommendation Follow-Ups
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($recommendation_no ?: 'Edit Recommendation') : 'New Service Recommendation' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Follow up on a technician-recommended future service.</flux:text>
        </div>

        <flux:separator />

        {{-- RECOMMENDATION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Recommendation</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What's recommended and why.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Customer…" autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Search customer…" /></x-slot>
                        @foreach ($this->customers as $c)<flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ trim($c->first_name.' '.$c->last_name) }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                        @foreach ($this->vehicles as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="priority" variant="listbox" label="Priority" required>
                        @foreach ($SRF::priorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <flux:input wire:model="recommended_service" label="Recommended Service" placeholder="e.g. Tyre Replace, Timing Belt Replace" />
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="recommendation_type" variant="listbox" searchable clearable label="Type" placeholder="Engine / Brake…">
                        @foreach ($SRF::recommendationTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="recommendation_reason" variant="listbox" clearable label="Reason" placeholder="Worn out / Leakage…">
                        @foreach ($SRF::recommendationReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="recommendation_category" variant="listbox" clearable label="Category" placeholder="Safety / Preventive…">
                        @foreach ($SRF::recommendationCategories() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="invoice_reference" label="Invoice / Inspection Ref" placeholder="Invoice / photos ref" class:input="font-mono" />
                    <flux:input wire:model="estimate_reference" label="Estimate / Package Ref" placeholder="AMC / combo / package" />
                    <flux:input.group label="Estimated Value">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="estimated_value" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- FOLLOW-UP --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Follow-up</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Owner, attempt, response and status.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="follow_up_by_id" variant="listbox" searchable clearable label="Follow-up By" placeholder="Advisor / CRM…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="fb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="reminder_frequency" variant="listbox" clearable label="Reminder Frequency" placeholder="Config only — never sent">
                        @foreach ($SRF::reminderFrequencies() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="follow_up_attempt" variant="listbox" clearable label="Attempt" placeholder="1st / 2nd…">
                        @foreach ($SRF::followUpAttempts() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode" variant="listbox" clearable label="Mode" placeholder="WhatsApp / Call…">
                        @foreach ($SRF::followUpModes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_response" variant="listbox" clearable label="Customer Response" placeholder="What they said…">
                        @foreach ($SRF::customerResponses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Follow-up Status" required>
                        @foreach ($SRF::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_retention" variant="listbox" clearable label="Customer Retention" placeholder="Active / Lost / Recovered">
                        @foreach ($SRF::retentions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.status === 'lost_opportunity'" x-cloak>
                    <flux:select wire:model="lost_reason" variant="listbox" clearable label="Lost Reason" placeholder="Why lost…" class="md:max-w-sm">
                        @foreach ($SRF::lostReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="lost_reason" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" x-data>
                    <flux:select wire:model="customer_satisfaction" variant="listbox" clearable label="Customer Satisfaction" placeholder="Satisfied / Dissatisfied">
                        @foreach ($SRF::satisfactions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model.live="escalation" variant="listbox" clearable label="Escalation" placeholder="If escalated…">
                        @foreach ($SRF::escalations() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.escalation" x-cloak class="md:col-span-2 md:max-w-sm">
                        <flux:select wire:model="escalation_reason" variant="listbox" clearable label="Escalation Reason" placeholder="Why escalated…">
                            @foreach ($SRF::escalationReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="escalation_reason" />
                    </div>
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
            <flux:button :href="route('service-recommendation-follow-up.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create recommendation' }}</flux:button>
        </div>
    </form>
</div>
