@php($AR = \App\Modules\AdvanceReceipt\Models\AdvanceReceipt::class)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('advance-receipt.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Advance Receipt Entry
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($receipt_no ?: 'Edit Receipt') : 'New Advance Receipt' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Record an advance payment against a job card / estimate.</flux:text>
        </div>

        <flux:separator />

        {{-- CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Against</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Job card, estimate and customer.</flux:text>
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
                    <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurer" placeholder="Optional">
                        @foreach ($this->companies as $co)
                            <flux:select.option :value="$co->id" wire:key="co-{{ $co->id }}">{{ $co->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="workshop_department_id" variant="listbox" searchable clearable label="Department" placeholder="Optional">
                        @foreach ($this->departments as $d)
                            <flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="employee_id" variant="listbox" searchable clearable label="Received By" placeholder="Cashier / advisor">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="em-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- PAYMENT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Payment</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Amount, mode and status.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input.group label="Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="amount" type="number" step="0.01" min="0.01" class:input="text-right font-mono" required />
                    </flux:input.group>
                    <flux:select wire:model.live="payment_mode_id" variant="listbox" searchable label="Payment Mode" placeholder="Cash / UPI / Cheque…" required>
                        @foreach ($this->paymentModes as $pm)
                            <flux:select.option :value="$pm->id" wire:key="pm-{{ $pm->id }}">{{ $pm->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="payment_status" variant="listbox" label="Payment Status" required>
                        @foreach ($AR::paymentStatuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="reference_no" label="Reference / UTR No" placeholder="Txn reference" class:input="font-mono uppercase" />
                    <flux:select wire:model="bank_id" variant="listbox" searchable clearable label="Bank" placeholder="Optional">
                        @foreach ($this->banks as $b)
                            <flux:select.option :value="$b->id" wire:key="bk-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:date-picker wire:model="received_at" label="Received At" with-today selectable-header fixed-weeks type="input" clearable />
                </div>

                <div x-show="$wire.payment_status === 'cancelled'" x-cloak>
                    <flux:select wire:model="cancellation_reason_id" variant="listbox" clearable label="Cancellation Reason" placeholder="Why cancelled" class="md:max-w-sm">
                        @foreach ($this->cancellationReasons as $cr)
                            <flux:select.option :value="$cr->id" wire:key="cr-{{ $cr->id }}">{{ $cr->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="cancellation_reason_id" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="difference_amount" type="number" step="0.01" label="Difference Amount" class:input="text-right font-mono" />
                    <flux:select wire:model="difference_reason_id" variant="listbox" clearable label="Difference Reason" placeholder="If short/excess…">
                        @foreach ($this->differenceReasons as $dr)
                            <flux:select.option :value="$dr->id" wire:key="dr-{{ $dr->id }}">{{ $dr->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CHEQUE (revealed for cheque mode) --}}
        @php($chequeModeId = optional($this->paymentModes->firstWhere('name', 'CHEQUE'))->id)
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8" x-data
            x-show="Number($wire.payment_mode_id) === {{ $chequeModeId ?? 0 }}" x-cloak>
            <div>
                <flux:heading size="lg">Cheque</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Cheque tracking and bounce handling.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="cheque_no" label="Cheque No" placeholder="Number" class:input="font-mono" />
                    <flux:date-picker wire:model="cheque_date" label="Cheque Date" with-today selectable-header fixed-weeks type="input" clearable />
                    <flux:select wire:model.live="cheque_status" variant="listbox" clearable label="Cheque Status" placeholder="Received / Cleared…">
                        @foreach ($AR::chequeStatuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.cheque_status === 'returned'" x-cloak>
                    <flux:select wire:model="cheque_bounce_reason_id" variant="listbox" clearable label="Cheque Bounce Reason" placeholder="Why returned" class="md:max-w-sm">
                        @foreach ($this->chequeBounceReasons as $bb)
                            <flux:select.option :value="$bb->id" wire:key="bb-{{ $bb->id }}">{{ $bb->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Attachments</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Cheque copy / UTR / deposit slip / advice (PDF/image).</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                </div>
                @forelse ($attachments as $i => $att)
                    <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Type…">
                            @foreach ($AR::attachmentTypes() as $key => $label)
                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
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
                        No files yet. Click <span class="font-medium">Add file</span> to attach proof of payment.
                    </div>
                @endforelse
                <flux:textarea wire:model="notes" label="Notes" placeholder="Optional remarks." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('advance-receipt.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Record receipt' }}</flux:button>
        </div>
    </form>
</div>
