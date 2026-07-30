@php($DD = \App\Modules\DocumentDelivery\Models\DocumentDelivery::class)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('document-delivery.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Document Delivery
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($delivery_no ?: 'Edit Delivery') : 'New Document Delivery' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Track documents delivered to the customer / insurer.</flux:text>
        </div>

        <flux:separator />

        {{-- WHO & WHERE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Recipient</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Job card, customer, insurer and where it's going.</flux:text>
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Search customer…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Name or phone…" /></x-slot>
                        @foreach ($this->customers as $c)
                            <flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="recipient_type" variant="listbox" clearable label="Recipient" placeholder="Owner / On behalf…">
                        @foreach ($DD::recipientTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="If delivering to insurer…">
                    @foreach ($this->companies as $co)
                        <flux:select.option :value="$co->id" wire:key="co-{{ $co->id }}">{{ $co->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="delivery_state" label="State" placeholder="State" />
                    <flux:input wire:model="delivery_city" label="City" placeholder="City" />
                    <flux:input wire:model="delivery_area" label="Area" placeholder="Area / locality" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- DELIVERY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Delivery</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who delivers, how, and the acknowledgement.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="advisor_employee_id" variant="listbox" searchable clearable label="Advisor (created by)" placeholder="Optional">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="driver_employee_id" variant="listbox" searchable clearable label="Delivered By (driver)" placeholder="Optional">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="drv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model.live="delivery_mode" variant="listbox" clearable label="Delivery Mode" placeholder="Hand / Porter / Courier">
                        @foreach ($DD::deliveryModes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-data x-show="$wire.delivery_mode === 'courier' || $wire.delivery_mode === 'porter'" x-cloak>
                        <flux:select wire:model="courier_company_id" variant="listbox" searchable clearable label="Transport / Courier">
                            @foreach ($this->couriers as $cc)
                                <flux:select.option :value="$cc->id" wire:key="cc-{{ $cc->id }}">{{ $cc->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:select wire:model="acknowledgement_type" variant="listbox" clearable label="Acknowledgement" placeholder="Signature / OTP…">
                        @foreach ($DD::acknowledgementTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CHECKLIST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Document Checklist</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Tick each document as it's handed over.</flux:text>
            </div>
            <div class="space-y-2 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add document</flux:button>
                </div>
                @forelse ($items as $i => $item)
                    <div wire:key="doc-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_150px_auto] gap-2 items-end p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:input wire:model="items.{{ $i }}.document_name" size="sm" placeholder="Document name" required list="dd-standard-docs" />
                        <flux:checkbox wire:model="items.{{ $i }}.is_delivered" label="Delivered" />
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" class="h-9!" />
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No documents on this delivery. Click <span class="font-medium">Add document</span>.
                    </div>
                @endforelse
                <datalist id="dd-standard-docs">
                    @foreach ($DD::standardDocuments() as $doc)
                        <option value="{{ $doc }}">
                    @endforeach
                </datalist>
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status & Follow-up</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Reminders are configured only — nothing is sent.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Delivery Status" required>
                        @foreach ($DD::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.status === 'delivered'" x-cloak>
                        <flux:date-picker wire:model="delivered_at" label="Delivered At" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                        <flux:error name="delivered_at" />
                    </div>
                </div>

                <div x-show="$wire.status === 'returned' || $wire.status === 're_sent'" x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <flux:select wire:model="delivery_failure_reason" variant="listbox" clearable label="Delivery Failure Reason" placeholder="Why it failed">
                            @foreach ($DD::failureReasons() as $key => $label)
                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="missing_document_reason_id" variant="listbox" clearable label="Missing Document Reason" placeholder="If a doc is missing">
                            @foreach ($this->missingReasons as $mr)
                                <flux:select.option :value="$mr->id" wire:key="mr-{{ $mr->id }}">{{ $mr->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model.live="reminder_frequency" variant="listbox" clearable label="Auto Reminder" placeholder="Off">
                        @foreach ($DD::reminderFrequencies() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.reminder_frequency === 'custom'" x-cloak>
                        <flux:input wire:model="reminder_custom_days" type="number" min="1" max="90" label="Every N days" />
                    </div>
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / SMS / …">
                        @foreach ($this->followUpModes as $fm)
                            <flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Delivery remarks." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('document-delivery.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create delivery' }}</flux:button>
        </div>
    </form>
</div>
