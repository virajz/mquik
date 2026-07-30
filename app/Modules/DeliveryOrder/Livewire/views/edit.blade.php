@php($DO = \App\Modules\DeliveryOrder\Models\DeliveryOrder::class)
@php($ATT = \App\Modules\DeliveryOrder\Models\DeliveryOrderAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('delivery-order.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Delivery Order (DO)
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($do_no ?: 'Edit DO') : 'New Delivery Order' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Record the insurance DO against a claim before delivering the vehicle.</flux:text>
        </div>

        <flux:separator />

        {{-- CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Context</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Job card, proforma and the claim.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="JC…" autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)<flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model.live="proforma_approval_id" variant="listbox" searchable clearable :filter="false" label="Proforma Ref" placeholder="Approved proforma…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="proformaSearch" placeholder="Search proforma…" /></x-slot>
                        @foreach ($this->proformas as $pf)<flux:select.option :value="$pf->id" wire:key="pf-{{ $pf->id }}">{{ $pf->approval_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="Insurer…">
                        @foreach ($this->insuranceCompanies as $ic)<flux:select.option :value="$ic->id" wire:key="ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>@endforeach
                    </flux:select>
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
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="employee_id" variant="listbox" searchable clearable label="Employee" placeholder="Handled by…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="em-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="surveyor_name" label="Surveyor" placeholder="Surveyor name" />
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / Email…">
                        @foreach ($this->followUpModes as $fm)<flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CLAIM --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Insurance Claim</flux:heading>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 min-w-0">
                <flux:input wire:model="claim_number" label="Claim Number" placeholder="Claim no" class:input="font-mono" />
                <flux:date-picker wire:model="claim_date" label="Claim Date" with-today selectable-header fixed-weeks type="input" />
                <flux:input wire:model="policy_number" label="Policy Number" placeholder="Policy no" class:input="font-mono" />
            </div>
        </section>

        <flux:separator />

        {{-- DO --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Delivery Order</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Amount, mismatch and status.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data="{
                get mismatch() {
                    const p = parseFloat($wire.proforma_amount);
                    const d = parseFloat($wire.do_amount);
                    return !isNaN(p) && !isNaN(d) && p !== d;
                }
            }">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <flux:input.group label="Proforma Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model.live="proforma_amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                    <flux:input.group label="DO Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model.live="do_amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                    <div x-show="mismatch" x-cloak>
                        <flux:text size="sm" class="text-red-600 dark:text-red-400 font-medium">
                            <flux:icon.exclamation-triangle class="inline size-4 -mt-0.5" /> Amount mismatch vs proforma
                        </flux:text>
                    </div>
                </div>
                <div x-show="mismatch" x-cloak>
                    <flux:select wire:model="mismatch_reason" variant="listbox" clearable label="Mismatch Reason" placeholder="Why the DO differs…" class="md:max-w-sm">
                        @foreach ($DO::mismatchReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="mismatch_reason" />
                </div>
                <flux:textarea wire:model="do_description" label="DO Description" rows="2" placeholder="What the DO covers." />
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="status" variant="listbox" label="DO Status" required>
                        @foreach ($DO::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:date-picker wire:model="do_received_at" label="DO Received At" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                    <flux:date-picker wire:model="do_entry_at" label="DO Entry At" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end" x-data>
                    <flux:select wire:model.live="reminder_frequency" variant="listbox" clearable label="Reminder Frequency" placeholder="Config only — never sent">
                        @foreach ($DO::reminderFrequencies() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.reminder_frequency === 'custom'" x-cloak>
                        <flux:input wire:model="reminder_custom_days" type="number" min="1" max="90" label="Every N days" class:input="font-mono" />
                        <flux:error name="reminder_custom_days" />
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Proforma copy / DO copy / surveyor / customer consent.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="DO remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('delivery-order.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Record DO' }}</flux:button>
        </div>
    </form>
</div>
