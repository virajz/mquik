@php($JCA = \App\Modules\JobCardCancelApproval\Models\JobCardCancelApproval::class)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('job-card-cancel-approval.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Job Card Cancel Approval
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($approval_no ?: 'Edit Request') : 'New Cancellation Request' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Admin approval workflow for cancelling a job card.</flux:text>
        </div>

        <flux:separator />

        {{-- REQUEST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Request</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Which job card and why.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Link a job card…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="employee_id" variant="listbox" searchable clearable label="Requested By" placeholder="Advisor / staff">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="em-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" searchable clearable label="Department" placeholder="Optional">
                        @foreach ($this->departments as $d)
                            <flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="service_type_id" variant="listbox" searchable clearable label="Job Type" placeholder="Service type…">
                        @foreach ($this->serviceTypes as $st)
                            <flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="cancellation_type" variant="listbox" clearable label="Cancellation Type" placeholder="Wrong Entry / Duplicate…">
                        @foreach ($JCA::cancellationTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="cancel_reason_id" variant="listbox" searchable clearable label="Cancellation Reason" placeholder="Reason…">
                        @foreach ($this->cancelReasons as $cr)
                            <flux:select.option :value="$cr->id" wire:key="cr-{{ $cr->id }}">{{ $cr->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- APPROVAL --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Approval</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Hierarchy level and decision.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="approval_level" variant="listbox" clearable label="Approval Level" placeholder="Current level…">
                        @foreach ($JCA::approvalLevels() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="status" variant="listbox" label="Status" required>
                        @foreach ($JCA::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div x-show="['approved','rejected'].includes($wire.status)" x-cloak>
                    <flux:input wire:model="decided_at" type="datetime-local" label="Decided At" class="md:max-w-xs" />
                    <flux:error name="decided_at" />
                </div>

                <div x-show="$wire.status === 'rejected'" x-cloak>
                    <flux:select wire:model="approval_rejection_reason" variant="listbox" clearable label="Approval Rejection Reason" placeholder="Why rejected" class="md:max-w-sm">
                        @foreach ($JCA::rejectionReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="approval_rejection_reason" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- IMPACT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Impact & Refund</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Downstream effects of cancelling.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:checkbox.group label="Cancellation Impact">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach ($JCA::impactOptions() as $key => $label)
                            <flux:checkbox wire:model="impacts" value="{{ $key }}" label="{{ $label }}" />
                        @endforeach
                    </div>
                </flux:checkbox.group>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="refund_status" variant="listbox" clearable label="Refund Status" placeholder="If refund involved…">
                        @foreach ($JCA::refundStatuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / SMS / …">
                        @foreach ($this->followUpModes as $fm)
                            <flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Justification / review remarks." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('job-card-cancel-approval.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Raise request' }}</flux:button>
        </div>
    </form>
</div>
