@php($PRF = \App\Modules\PolicyRenewalFollowUp\Models\PolicyRenewalFollowUp::class)
@php($ATT = \App\Modules\PolicyRenewalFollowUp\Models\PolicyRenewalFollowUpAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('policy-renewal-follow-up.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Policy Renewal Follow-Ups
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($follow_up_no ?: 'Edit Follow-Up') : 'New Renewal Follow-Up' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Track an upcoming insurance policy renewal.</flux:text>
        </div>

        <flux:separator />

        {{-- POLICY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Policy</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">The expiring policy and customer.</flux:text>
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
                        @foreach ($PRF::priorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="Insurer…">
                        @foreach ($this->insuranceCompanies as $ic)<flux:select.option :value="$ic->id" wire:key="ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="insurance_policy_type_id" variant="listbox" clearable label="Policy Type" placeholder="Comprehensive / TP…">
                        @foreach ($this->policyTypes as $pt)<flux:select.option :value="$pt->id" wire:key="pt-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="policy_number" label="Policy No" placeholder="Policy number" class:input="font-mono" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:input wire:model="policy_start_date" type="date" label="Start Date" />
                    <flux:input wire:model="policy_end_date" type="date" label="End Date (Expiry)" />
                    <flux:input wire:model="renewal_reference" label="Renewal Ref" placeholder="Ref" class:input="font-mono" />
                    <flux:input.group label="Renewal Premium">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="renewal_premium" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- FOLLOW-UP --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Follow-up</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Reminder, attempt, response and status.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="assigned_by_id" variant="listbox" searchable clearable label="Assigned By" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ab-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="assigned_to_id" variant="listbox" searchable clearable label="Assigned To" placeholder="CRM / Executive…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="at-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="reminder_frequency" variant="listbox" clearable label="Reminder Frequency" placeholder="Config only — never sent">
                        @foreach ($PRF::reminderFrequencies() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="follow_up_attempt" variant="listbox" clearable label="Attempt" placeholder="1st / 2nd…">
                        @foreach ($PRF::followUpAttempts() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode" variant="listbox" clearable label="Mode" placeholder="WhatsApp / Call…">
                        @foreach ($PRF::followUpModes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_response" variant="listbox" clearable label="Customer Response" placeholder="What they said…">
                        @foreach ($PRF::customerResponses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Follow-up Status" required>
                        @foreach ($PRF::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_retention" variant="listbox" clearable label="Customer Retention" placeholder="Active / Lost / Recovered">
                        @foreach ($PRF::retentions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.status === 'lost_opportunity'" x-cloak>
                    <flux:select wire:model="lost_reason" variant="listbox" clearable label="Lost Renewal Reason" placeholder="Why lost…" class="md:max-w-sm">
                        @foreach ($PRF::lostReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="lost_reason" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" x-data>
                    <flux:select wire:model.live="escalation" variant="listbox" clearable label="Escalation" placeholder="If escalated…">
                        @foreach ($PRF::escalations() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.escalation" x-cloak>
                        <flux:select wire:model="escalation_reason" variant="listbox" clearable label="Escalation Reason" placeholder="Why escalated…">
                            @foreach ($PRF::escalationReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">RC / Aadhar / PAN / previous or renewed policy / quote / payment receipt.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Follow-up remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('policy-renewal-follow-up.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create follow-up' }}</flux:button>
        </div>
    </form>
</div>
