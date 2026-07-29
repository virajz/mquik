@php($PFA = \App\Modules\ProformaApproval\Models\ProformaApproval::class)
@php($ATT = \App\Modules\ProformaApproval\Models\ProformaApprovalAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('proforma-approval.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Proforma Approval
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($approval_no ?: 'Edit Approval') : 'New Proforma Approval' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Staged approval of a proforma before invoice conversion.</flux:text>
        </div>

        <flux:separator />

        {{-- STAGE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Stage & Context</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Where the proforma is in the approval chain.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="approval_stage" variant="listbox" clearable label="Approval Stage" placeholder="Stage…" autofocus>
                        @foreach ($PFA::stages() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="approval_authority" variant="listbox" clearable label="Approval Authority" placeholder="Who approves…">
                        @foreach ($PFA::approvalAuthorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="priority" variant="listbox" label="Priority" required>
                        @foreach ($PFA::priorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="JC…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)<flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="sales_estimate_id" variant="listbox" searchable clearable :filter="false" label="Estimate" placeholder="Estimate…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="estimateSearch" placeholder="Search estimate…" /></x-slot>
                        @foreach ($this->estimates as $es)<flux:select.option :value="$es->id" wire:key="es-{{ $es->id }}">{{ $es->estimate_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="proforma_reference" label="Proforma Print Ref" placeholder="Format-1 / Format-2 ref" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Customer…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Search customer…" /></x-slot>
                        @foreach ($this->customers as $c)<flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ trim($c->first_name.' '.$c->last_name) }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                        @foreach ($this->vehicles as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="If insurance…">
                        @foreach ($this->insuranceCompanies as $ic)<flux:select.option :value="$ic->id" wire:key="ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="service_type_id" variant="listbox" clearable label="Service Type" placeholder="Type…">
                        @foreach ($this->serviceTypes as $st)<flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input.group label="Proforma Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- PEOPLE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">People</flux:heading>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 min-w-0">
                <flux:select wire:model="billing_executive_id" variant="listbox" searchable clearable label="Billing Executive" placeholder="Prepared by…">
                    @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="be-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="store_incharge_id" variant="listbox" searchable clearable label="Store In-charge" placeholder="Store…">
                    @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="si-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Service Advisor" placeholder="Advisor…">
                    @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ad-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="technician_id" variant="listbox" searchable clearable label="Technician" placeholder="Technician…">
                    @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="tc-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="vendor_id" variant="listbox" searchable clearable label="Service Contractor" placeholder="Outside labour vendor…">
                    @foreach ($this->vendors as $v)<flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / Email…">
                    @foreach ($this->followUpModes as $fm)<flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>@endforeach
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- CHECKPOINTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Checkpoints</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each approver's verification points — tick OK or flag.</flux:text>
            </div>
            <div class="space-y-2 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addCheckpoint">Add checkpoint</flux:button>
                </div>
                @forelse ($checkpoints as $i => $cp)
                    <div wire:key="cp-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[150px_1fr_120px_auto] gap-2 items-end p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:select wire:model.live="checkpoints.{{ $i }}.role" variant="listbox" size="sm" label="Role">
                            @foreach ($PFA::checkpointRoles() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:select wire:model="checkpoints.{{ $i }}.checkpoint" variant="listbox" size="sm" searchable clearable label="Checkpoint" placeholder="Pick a checkpoint…">
                            @foreach ($this->checkpointOptions($cp['role']) as $key => $label)<flux:select.option :value="$key" wire:key="cpo-{{ $i }}-{{ $key }}">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:select wire:model="checkpoints.{{ $i }}.status" variant="listbox" size="sm" label="Status">
                            @foreach ($PFA::checkpointStatuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeCheckpoint({{ $i }})" class="h-9!" />
                        <flux:input wire:model="checkpoints.{{ $i }}.note" size="sm" placeholder="Note (optional)" class="md:col-span-4" />
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">No checkpoints added.</div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        {{-- REASONS & STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Reasons & Status</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="loss_reason" variant="listbox" clearable label="Loss Reason" placeholder="If loss…">
                        @foreach ($PFA::lossReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="missing_reason" variant="listbox" clearable label="Missing Reason" placeholder="If missing…">
                        @foreach ($PFA::missingReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="discount_type" variant="listbox" clearable label="Discount Type" placeholder="If discount…">
                        @foreach ($PFA::discountTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Approval Status" required>
                        @foreach ($PFA::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.status === 'return_for_correction'" x-cloak>
                        <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Return / Rejection Reason" placeholder="Why returned…">
                            @foreach ($PFA::rejectionReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="rejection_reason" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="store_approved_at" type="datetime-local" label="Store Approved At" />
                    <flux:input wire:model="advisor_approved_at" type="datetime-local" label="Advisor Approved At" />
                    <flux:input wire:model="admin_approved_at" type="datetime-local" label="Admin Approved At" />
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Proforma print (Format-1 / Format-2) / approval screenshot.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Approval remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('proforma-approval.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create approval' }}</flux:button>
        </div>
    </form>
</div>
