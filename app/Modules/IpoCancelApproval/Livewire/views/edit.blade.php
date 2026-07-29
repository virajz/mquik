@php($ICR = \App\Modules\IpoCancelApproval\Models\IpoCancelApproval::class)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('ipo-cancel-approval.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> IPO Cancel Approval
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($cancel_no ?: 'Edit Request') : 'New IPO Cancel Request' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Request to cancel specific un-issued parts on an internal part order.</flux:text>
        </div>

        <flux:separator />

        {{-- REFERENCES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Part & References</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Which IPO / part is being cancelled.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="internal_part_order_id" variant="listbox" searchable clearable :filter="false" label="IPO Reference" placeholder="Internal part order…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="ipoSearch" placeholder="Search IPO…" /></x-slot>
                        @foreach ($this->ipos as $ipo)
                            <flux:select.option :value="$ipo->id" wire:key="ipo-{{ $ipo->id }}">{{ $ipo->order_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Optional…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-[1fr_120px_120px] gap-3">
                    <flux:select wire:model="spare_id" variant="listbox" searchable clearable :filter="false" label="Spare" placeholder="Part…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="spareSearch" placeholder="Search spare…" /></x-slot>
                        @foreach ($this->spares as $s)
                            <flux:select.option :value="$s->id" wire:key="sp-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="quantity" type="number" step="0.01" min="0" label="Qty" class:input="text-right font-mono" />
                    <flux:select wire:model="uom_id" variant="listbox" clearable label="UOM" placeholder="Unit…">
                        @foreach ($this->uoms as $u)
                            <flux:select.option :value="$u->id" wire:key="uom-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>
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
                    <flux:select wire:model="employee_id" variant="listbox" searchable clearable label="Requested By" placeholder="Advisor / store…">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="em-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CANCELLATION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Cancellation</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Reason, category, impact and return.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="cancellation_reason" variant="listbox" clearable label="Cancellation Reason" placeholder="Why cancel…">
                        @foreach ($ICR::cancellationReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="cancellation_category" variant="listbox" clearable label="Category" placeholder="Operational / Customer…">
                        @foreach ($ICR::categories() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:checkbox.group label="Cancellation Impact">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach ($ICR::impactOptions() as $key => $label)
                            <flux:checkbox wire:model="impacts" value="{{ $key }}" label="{{ $label }}" />
                        @endforeach
                    </div>
                </flux:checkbox.group>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="issue_status" variant="listbox" clearable label="Issue Status" placeholder="Pending / Issued…">
                        @foreach ($ICR::issueStatuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="return_status" variant="listbox" clearable label="Return Status" placeholder="Pending / Returned…">
                        @foreach ($ICR::returnStatuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="return_type" variant="listbox" clearable label="Return Type" placeholder="Full / Partial…">
                        @foreach ($ICR::returnTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
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
                        @foreach ($ICR::approvalLevels() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="status" variant="listbox" label="Cancel Status" required>
                        @foreach ($ICR::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div x-show="['approved','rejected'].includes($wire.status)" x-cloak>
                    <flux:input wire:model="decided_at" type="datetime-local" label="Decided At" class="md:max-w-xs" />
                    <flux:error name="decided_at" />
                </div>

                <div x-show="$wire.status === 'rejected'" x-cloak>
                    <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason" placeholder="Why rejected" class="md:max-w-sm">
                        @foreach ($ICR::rejectionReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="rejection_reason" />
                </div>

                {{-- Attachments --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Document Attachments</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                    </div>
                    @forelse ($attachments as $i => $att)
                        <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end mb-2 p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <div>
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                                @if (! empty($att['path']))
                                    <flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>
                                @endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">PDF / image proof (optional).</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Justification / review remarks." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('ipo-cancel-approval.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Raise request' }}</flux:button>
        </div>
    </form>
</div>
