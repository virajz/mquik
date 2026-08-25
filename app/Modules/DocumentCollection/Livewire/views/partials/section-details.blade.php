@php($lean = $lean ?? false)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
    <div>
        <flux:heading size="lg">Customer & Vehicle</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Whose documents are being collected, and for which vehicle.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        @unless ($lean)
            {{-- Job-first: most collections start from a job card, and picking it
                 fills the customer, vehicle, department and service type below. --}}
            <flux:select wire:model.live="job_card_id" variant="listbox" searchable clearable :filter="false"
                label="Job No." placeholder="Search job no, reg no or customer…">
                <x-slot name="search">
                    <flux:select.search wire:model.live.debounce.250ms="jobSearch" placeholder="JC-92100, GJ 05…, name…" />
                </x-slot>
                @foreach ($this->jobCardOptions as $jc)
                    <flux:select.option :value="$jc['id']" wire:key="dc-jc-{{ $jc['id'] }}">{{ $jc['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
        @endunless

        <flux:field>
            <flux:label>Customer</flux:label>
            <div class="flex items-stretch gap-2">
                <div class="flex-1 min-w-0">
                    <flux:select wire:model.live="customer_id" variant="listbox" searchable required placeholder="Pick a customer…" :filter="false">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Name or phone…" />
                        </x-slot>
                        @foreach ($this->customers as $c)
                            <flux:select.option :value="$c->id" wire:key="dc-cust-{{ $c->id }}">{{ trim($c->first_name.' '.($c->last_name ?? '')) }}{{ $c->phone ? ' · '.$c->phone : '' }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                @can('customer_master.create')
                    <flux:tooltip content="Quick add a new customer">
                        <flux:button icon="plus" variant="ghost" type="button" x-on:click="$flux.modal('customer-quick-add').show()" />
                    </flux:tooltip>
                @endcan
            </div>
            <flux:error name="customer_id" />
        </flux:field>

        <flux:field>
            <flux:label>Vehicle</flux:label>
            <div class="flex items-stretch gap-2">
                <div class="flex-1 min-w-0">
                    <flux:select wire:model.live="customer_vehicle_id" variant="listbox" searchable required :placeholder="$customer_id ? 'Pick a vehicle…' : 'Pick a customer first'" :disabled="! $customer_id">
                        @foreach ($this->customerVehicles as $v)
                            <flux:select.option :value="$v['id']" wire:key="dc-cv-{{ $v['id'] }}">{{ $v['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                @can('customer_vehicle_master.create')
                    <flux:tooltip content="Quick add a new vehicle (creates the customer too)">
                        <flux:button icon="plus" variant="ghost" type="button" x-on:click="$flux.modal('customer-vehicle-quick-add').show()" />
                    </flux:tooltip>
                @endcan
            </div>
            <flux:error name="customer_vehicle_id" />
        </flux:field>
    </div>
</section>

<flux:separator />

<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
    <div>
        <flux:heading size="lg">Source & Purpose</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Why these documents are being collected, and who is handling it.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="request_type" variant="listbox" label="Request Source" required>
                @foreach (\App\Modules\DocumentCollection\Models\DocumentCollection::requestTypes() as $key => $label)
                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="purpose" variant="listbox" clearable label="Purpose" placeholder="Why are we collecting…" required>
                @foreach (\App\Modules\DocumentCollection\Models\DocumentCollection::purposes() as $key => $label)
                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="created_by_advisor_id" variant="listbox" searchable label="Advisor (created by)" placeholder="Pick an advisor…" required>
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="dc-adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
            @unless ($lean)
                <flux:select wire:model="collected_by_driver_id" variant="listbox" searchable clearable label="Driver (collected by)" placeholder="Pick a driver…">
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="dc-drv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endunless
        </div>
        @unless ($lean)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Derived from the documents themselves — received when every
                     required one is in. Rejection/cancellation stay deliberate. --}}
                <flux:field>
                    <flux:label>Status</flux:label>
                    <div class="flex items-center h-10">
                        <flux:badge :color="match ($status) {
                            'pending' => 'zinc', 'requested' => 'amber', 'received' => 'lime',
                            'rejected' => 'red', 'cancelled' => 'zinc', default => 'zinc',
                        }" size="sm">{{ \App\Modules\DocumentCollection\Models\DocumentCollection::statuses()[$status] ?? $status }}</flux:badge>
                    </div>
                    <flux:description>Follows the document checklist.</flux:description>
                </flux:field>
                <flux:select wire:model.live="department_id" variant="listbox" searchable label="Department" placeholder="Pick a department…" required>
                    @foreach ($this->departments as $d)
                        <flux:select.option :value="$d->id" wire:key="dc-dept-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="service_type_id" variant="listbox" searchable clearable label="Service Type"
                    :placeholder="$department_id ? 'Pick a service type…' : 'Pick a department first'"
                    :disabled="! $department_id" required>
                    @foreach ($this->serviceTypes as $st)
                        <flux:select.option :value="$st->id" wire:key="dc-st-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endunless
    </div>
</section>

<flux:separator />

<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
    <div>
        <flux:heading size="lg">Insurance</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Policy & claim details (for insurance jobs).</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="Pick a company…">
                @foreach ($this->insuranceCompanies as $ic)
                    <flux:select.option :value="$ic->id" wire:key="dc-ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="policy_no" label="Policy No." placeholder="Optional" />
            <flux:select wire:model="insurance_policy_type_id" variant="listbox" searchable clearable label="Policy Type" placeholder="Comprehensive / TP…">
                @foreach ($this->policyTypes as $pt)
                    <flux:select.option :value="$pt->id" wire:key="dc-pt-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="claim_type_id" variant="listbox" searchable clearable label="Claim Type" placeholder="Cashless / Reimbursement…">
                @foreach ($this->claimTypes as $clt)
                    <flux:select.option :value="$clt->id" wire:key="dc-clt-{{ $clt->id }}">{{ $clt->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>
</section>

<flux:separator />

<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
    <div>
        <flux:heading size="lg">Checklist</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Pick the document set and verification checklist — their items seed the Documents &amp; Verification tabs.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model.live="checklist_template_id" variant="listbox" searchable label="Document Checklist" placeholder="Pick a template…" required>
                @foreach ($this->checklistTemplates as $t)
                    <flux:select.option :value="$t->id" wire:key="dc-tpl-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="verification_template_id" variant="listbox" searchable label="Verification Checklist" placeholder="Pick a template…" required>
                @foreach ($this->checklistTemplates as $t)
                    <flux:select.option :value="$t->id" wire:key="dc-vtpl-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @unless ($lean)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Stamped when the record is created — the record IS the request.
                     Each document stamps its own received moment in the checklist. --}}
                <flux:field>
                    <flux:label>Requested Date</flux:label>
                    <flux:input :value="$requested_date ? \Illuminate\Support\Carbon::parse($requested_date)->format('d/m/Y') : now()->format('d/m/Y')" readonly class:input="text-zinc-500" />
                </flux:field>
                <flux:field>
                    <flux:label>Requested Time</flux:label>
                    <flux:input :value="$requested_time ?: now()->format('H:i')" readonly class:input="text-zinc-500 font-mono" />
                    <flux:description>Stamped on save; each document records its own receive time.</flux:description>
                </flux:field>
                {{-- Custom-days / retention-days reveal client-side (Alpine); the
                     server still requires them via requiredIf, so they stay authoritative. --}}
                <flux:select wire:model="reminder_frequency" variant="listbox" clearable label="Auto Reminder" placeholder="None">
                    @foreach (\App\Modules\DocumentCollection\Models\DocumentCollection::reminderFrequencies() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div x-show="$wire.reminder_frequency === '{{ \App\Modules\DocumentCollection\Models\DocumentCollection::REMINDER_CUSTOM }}'" x-cloak>
                    <flux:input
                        type="number"
                        wire:model="reminder_custom_days"
                        label="Remind Every (days)"
                        min="1"
                        placeholder="3"
                    />
                </div>

                <flux:select wire:model="retention" variant="listbox" label="Retention">
                    @foreach (\App\Modules\DocumentCollection\Models\DocumentCollection::retentions() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div x-show="$wire.retention === '{{ \App\Modules\DocumentCollection\Models\DocumentCollection::RETENTION_DELETE }}'" x-cloak>
                    <flux:input
                        type="number"
                        wire:model="retention_days"
                        label="Delete After (days)"
                        min="1"
                        placeholder="90"
                        description="Counted from the job card's closed date. The record is soft-deleted, so it stays recoverable."
                    />
                </div>
            </div>
        @endunless
    </div>
</section>
