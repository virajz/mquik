@php($SEA = \App\Modules\SalesEstimateApproval\Models\SalesEstimateApproval::class)
@php($ITEM = \App\Modules\SalesEstimateApproval\Models\SalesEstimateApprovalItem::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('sales-estimate-approval.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Sales Estimate Approval
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($approval_no ?: 'Edit Approval') : 'New Estimate Approval' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Customer / advisor / insurer approval of an estimate, line by line.</flux:text>
        </div>

        <flux:separator />

        {{-- REFERENCES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">References</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Job card, estimate, surveyor and insurer.</flux:text>
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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurer" placeholder="Company…">
                        @foreach ($this->companies as $co)
                            <flux:select.option :value="$co->id" wire:key="co-{{ $co->id }}">{{ $co->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
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
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- APPROVAL SETUP --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Approval Setup</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Type, who authorises, mode and parts preference.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="approval_type" variant="listbox" label="Approval Type" required>
                        @foreach ($SEA::approvalTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="approval_authorisation" variant="listbox" clearable label="Approval Authorisation" placeholder="Who must approve…">
                        @foreach ($SEA::approvalAuthorisations() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="approval_mode_id" variant="listbox" clearable label="Approval Mode" placeholder="WhatsApp / Email / Sig…">
                        @foreach ($this->approvalModes as $am)
                            <flux:select.option :value="$am->id" wire:key="am-{{ $am->id }}">{{ $am->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="parts_brand_preference" variant="listbox" clearable label="Parts Brand" placeholder="Genuine / Aftermarket / Any">
                        @foreach ($SEA::partsBrandPreferences() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="employee_id" variant="listbox" searchable clearable label="Advisor" placeholder="Optional">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- LINES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Line Items</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Spares / labour / package with a per-line decision and depreciation.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end gap-2">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem('spare')">Spare</flux:button>
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem('labour')">Labour</flux:button>
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem('package')">Package</flux:button>
                </div>

                @forelse ($items as $i => $item)
                    @php($qty = (float) ($item['quantity'] ?? 0))
                    @php($rate = (float) ($item['unit_rate'] ?? 0))
                    @php($dep = (float) ($item['depreciation_percent'] ?? 0))
                    @php($net = round($qty * $rate * (1 - $dep / 100), 2))
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[90px_1fr_auto] gap-2 items-end">
                            <flux:badge size="sm" :color="$item['line_type'] === 'labour' ? 'purple' : ($item['line_type'] === 'package' ? 'amber' : 'sky')">{{ ucfirst($item['line_type']) }}</flux:badge>
                            @if ($item['line_type'] === 'spare')
                                <flux:select wire:model="items.{{ $i }}.spare_id" variant="listbox" searchable size="sm" label="Spare" placeholder="Pick a spare…">
                                    @foreach ($this->spares as $s)
                                        <flux:select.option :value="$s->id" wire:key="ss-{{ $i }}-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @elseif ($item['line_type'] === 'labour')
                                <flux:select wire:model="items.{{ $i }}.labour_id" variant="listbox" searchable size="sm" label="Labour" placeholder="Pick a labour…">
                                    @foreach ($this->labours as $l)
                                        <flux:select.option :value="$l->id" wire:key="sl-{{ $i }}-{{ $l->id }}">{{ $l->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:select wire:model="items.{{ $i }}.service_package_id" variant="listbox" searchable size="sm" label="Package" placeholder="Pick a package…">
                                    @foreach ($this->packages as $pk)
                                        <flux:select.option :value="$pk->id" wire:key="sp-{{ $i }}-{{ $pk->id }}">{{ $pk->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @endif
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" class="h-9!" />
                        </div>
                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Line description (required)" required />
                        <flux:error name="items.{{ $i }}.description" />
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.inventory_group_id" variant="listbox" size="sm" searchable clearable label="Group" placeholder="PMS / Brake…">
                                @foreach ($this->inventoryGroups as $g)
                                    <flux:select.option :value="$g->id" wire:key="ig-{{ $i }}-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.hsn_id" variant="listbox" size="sm" searchable clearable label="HSN" placeholder="HSN…">
                                @foreach ($this->hsnCodes as $h)
                                    <flux:select.option :value="$h->id" wire:key="ih-{{ $i }}-{{ $h->id }}">{{ $h->code }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax" placeholder="GST…">
                                @foreach ($this->taxes as $tx)
                                    <flux:select.option :value="$tx->id" wire:key="it-{{ $i }}-{{ $tx->id }}">{{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.line_approval" variant="listbox" size="sm" label="Decision" placeholder="Repair / Replace…">
                                @foreach ($ITEM::lineApprovals() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 items-end">
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" />
                            <flux:input.group label="Rate">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.unit_rate" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                            <div class="grid grid-cols-2 gap-1">
                                <flux:select wire:model="items.{{ $i }}.depreciation_category" variant="listbox" size="sm" clearable label="Dep. Cat" placeholder="—">
                                    @foreach ($ITEM::depreciationCategories() as $key => $label)
                                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="items.{{ $i }}.depreciation_percent" type="number" step="0.01" min="0" max="100" size="sm" label="Dep %" class:input="text-right font-mono" />
                            </div>
                            <div class="text-sm">
                                <div class="text-xs text-zinc-500">Net (after dep.)</div>
                                <div class="font-mono font-medium">₹ {{ number_format($net, 2) }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        Add the estimate lines going for approval.
                    </div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        {{-- STATUS & FOLLOW-UP --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status & Follow-up</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Reminders are configured only — nothing is sent.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Approval Status" required>
                        @foreach ($SEA::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.status === 'rejected'" x-cloak>
                        <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason" placeholder="Why rejected">
                            @foreach ($SEA::rejectionReasons() as $key => $label)
                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="rejection_reason" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:date-picker wire:model="customer_approved_at" label="Customer Approved At" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                    <flux:date-picker wire:model="insurance_approved_at" label="Insurance Approved At" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model.live="reminder_frequency" variant="listbox" clearable label="Auto Reminder" placeholder="Off">
                        @foreach ($SEA::reminderFrequencies() as $key => $label)
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

                <flux:textarea wire:model="notes" label="Notes" placeholder="Approval remarks / query details." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('sales-estimate-approval.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create approval' }}</flux:button>
        </div>
    </form>
</div>
