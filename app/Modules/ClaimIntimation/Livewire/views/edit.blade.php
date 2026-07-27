@php($CI = \App\Modules\ClaimIntimation\Models\ClaimIntimation::class)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('claim-intimation.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Claim Intimation
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($intimation_no ?: 'Edit Claim') : 'New Claim Intimation' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Register an accidental insurance claim and notify the surveyor.</flux:text>
        </div>

        <flux:separator />

        {{-- VEHICLE & CUSTOMER --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Job & Vehicle</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What's being claimed and for whom.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Link a job card…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
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
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Search customer…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Name or phone…" /></x-slot>
                        @foreach ($this->customers as $c)
                            <flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable searchable label="Department" placeholder="Optional">
                        @foreach ($this->departments as $d)
                            <flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="employee_id" variant="listbox" clearable searchable label="Advisor" placeholder="Raised by…">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="em-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- POLICY & CLAIM --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Policy & Claim</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Insurer, policy and the nature of the claim.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="Insurer…">
                        @foreach ($this->companies as $co)
                            <flux:select.option :value="$co->id" wire:key="co-{{ $co->id }}">{{ $co->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="insurance_policy_type_id" variant="listbox" clearable label="Policy Type" placeholder="Comprehensive / Third-party…">
                        @foreach ($this->policyTypes as $pt)
                            <flux:select.option :value="$pt->id" wire:key="pt-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="policy_no" label="Policy No" placeholder="Policy number" class:input="font-mono uppercase" />
                    <flux:select wire:model="claim_type_id" variant="listbox" clearable label="Claim Type" placeholder="Cashless / Reimbursement…">
                        @foreach ($this->claimTypes as $ct)
                            <flux:select.option :value="$ct->id" wire:key="ct-{{ $ct->id }}">{{ $ct->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="damage_nature" variant="listbox" clearable label="Damage Type" placeholder="Accident / Fire…">
                        @foreach ($CI::damageNatures() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:input wire:model="claim_no" label="Claim No (from insurer)" placeholder="Filled once intimated" class:input="font-mono uppercase" class="md:max-w-sm" />
            </div>
        </section>

        <flux:separator />

        {{-- INTIMATION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Intimation</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">How and when the insurer was intimated, and the survey window.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="intimation_mode" variant="listbox" clearable label="Intimation Mode" placeholder="Email / Portal / …">
                        @foreach ($CI::intimationModes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="survey_tat" variant="listbox" clearable label="Survey TAT" placeholder="Within 24/48/72 hrs">
                        @foreach ($CI::surveyTats() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="status" variant="listbox" label="Status" required>
                        @foreach ($CI::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div x-show="$wire.status === 'intimated'" x-cloak>
                    <flux:input wire:model="intimated_at" type="datetime-local" label="Intimated At" class="md:max-w-xs" />
                    <flux:error name="intimated_at" />
                </div>

                <div x-show="$wire.status === 'pending'" x-cloak>
                    <flux:select wire:model="pending_reason" variant="listbox" clearable label="Pending Reason" placeholder="Why still pending" class="md:max-w-sm">
                        @foreach ($CI::pendingReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the surveyor / advisor should know." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('claim-intimation.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Register claim' }}</flux:button>
        </div>
    </form>
</div>
