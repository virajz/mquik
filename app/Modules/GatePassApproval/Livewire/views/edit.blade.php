@php($GPA = \App\Modules\GatePassApproval\Models\GatePassApproval::class)
@php($ATT = \App\Modules\GatePassApproval\Models\GatePassApprovalAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('gate-pass-approval.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Gate Pass Approval
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($approval_no ?: 'Edit Approval') : 'New Gate Pass Approval' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Approve vehicle delivery against outstanding.</flux:text>
        </div>

        <flux:separator />

        {{-- CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Context</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Customer, vehicle and who's raising it.</flux:text>
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
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="JC…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)<flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="requested_by_id" variant="listbox" searchable clearable label="Requested By" placeholder="Advisor / Cashier…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="rb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="priority" variant="listbox" label="Priority" required>
                        @foreach ($GPA::priorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:input wire:model="invoice_reference" label="Invoice Ref" placeholder="Invoice no" class:input="font-mono" />
                    <flux:input wire:model="po_reference" label="PO Ref" placeholder="PO no" class:input="font-mono" />
                    <flux:input wire:model="receipt_reference" label="Receipt Ref" placeholder="Receipt no" class:input="font-mono" />
                    <flux:input wire:model="outstanding_reference" label="Outstanding Ref" placeholder="Ref" class:input="font-mono" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- AMOUNTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Amounts</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Outstanding, exposure and approval authority are calculated automatically.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input.group label="Invoice Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model.live.debounce.400ms="invoice_amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                    <flux:input.group label="Receipt Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model.live.debounce.400ms="receipt_amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-zinc-50 dark:bg-zinc-900">
                    <div>
                        <flux:text size="sm" class="text-zinc-500">Outstanding Amount</flux:text>
                        <div class="mt-0.5 text-lg font-semibold tabular-nums @if (($outstanding_amount ?? 0) > 0) text-red-600 dark:text-red-400 @endif">₹{{ number_format((float) ($outstanding_amount ?? 0), 2) }}</div>
                    </div>
                    <div>
                        <flux:text size="sm" class="text-zinc-500">Credit Exposure</flux:text>
                        <div class="mt-0.5 text-lg font-semibold tabular-nums">₹{{ number_format((float) ($credit_exposure ?? 0), 2) }}</div>
                    </div>
                    <div>
                        <flux:text size="sm" class="text-zinc-500">Approval Authority</flux:text>
                        <div class="mt-0.5">
                            <flux:badge size="sm" :color="$approval_authority === 'admin_hr_owner' ? 'purple' : 'sky'">
                                {{ $GPA::approvalAuthorities()[$approval_authority] ?? '—' }}
                            </flux:badge>
                            <flux:text size="sm" class="text-zinc-400 ml-1">(matrix: ≤ ₹25k advisor)</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CREDIT & RISK --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Credit & Risk</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Why credit, commitment and risk.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="credit_type" variant="listbox" clearable label="Credit Type" placeholder="Partial / Full / PDC…">
                        @foreach ($GPA::creditTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="credit_reason" variant="listbox" searchable clearable label="Credit Reason" placeholder="Why credit…">
                        @foreach ($GPA::creditReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model="customer_commitment" variant="listbox" clearable label="Customer Commitment" placeholder="Verbal / Written">
                        @foreach ($GPA::customerCommitments() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="security_deposit" variant="listbox" clearable label="Security Deposit" placeholder="Cheque / Cash">
                        @foreach ($GPA::securityDeposits() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="risk_type" variant="listbox" clearable label="Risk Type" placeholder="Low / Med / High">
                        @foreach ($GPA::riskTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="risk_category" variant="listbox" clearable label="Risk Category" placeholder="If risky…">
                        @foreach ($GPA::riskCategories() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Approval</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Approval Status" required>
                        @foreach ($GPA::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / Email…">
                        @foreach ($this->followUpModes as $fm)<flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.status === 'cancelled'" x-cloak>
                    <flux:select wire:model="cancellation_reason" variant="listbox" clearable label="Cancellation Reason" placeholder="Why cancelled" class="md:max-w-sm">
                        @foreach ($GPA::cancellationReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="cancellation_reason" />
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Customer request letter / form / approval note / payment commitment / PDC.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Approval remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('gate-pass-approval.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create request' }}</flux:button>
        </div>
    </form>
</div>
