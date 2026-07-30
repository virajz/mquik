@php($IPI = \App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry::class)
@php($ITEM = \App\Modules\InternalPartsInquiry\Models\InternalPartsInquiryItem::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('internal-parts-inquiry.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Internal Parts Inquiries
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($ipi_no ?: 'Edit Inquiry') : 'New Internal Parts Inquiry' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Advisor asks the store for part rate, availability and brand.</flux:text>
        </div>

        <flux:separator />

        {{-- DETAILS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Inquiry</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Type, who's asking, and who in the store is answering.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="inquiry_type" variant="listbox" label="Inquiry Type" required autofocus>
                        @foreach ($IPI::inquiryTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="priority_id" variant="listbox" clearable label="Priority" placeholder="Normal / High / Urgent">
                        @foreach ($this->priorities as $p)
                            <flux:select.option :value="$p->id" wire:key="pr-{{ $p->id }}">{{ $p->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="requested_by_employee_id" variant="listbox" searchable label="Requested By" placeholder="Advisor…" required>
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="rq-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="target_employee_id" variant="listbox" searchable clearable label="Store / Answered By" placeholder="Store incharge…">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="tg-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" searchable clearable label="Department" placeholder="Optional">
                        @foreach ($this->departments as $d)
                            <flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="approval_authority" variant="listbox" clearable label="Approval Authority" placeholder="Who approves…">
                        @foreach ($IPI::approvalAuthorities() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vehicle & Supplier</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Job card, customer, vehicle and the supplier/vendor being asked.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Link a job card…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" />
                        </x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Supplier / Vendor" placeholder="Who is being asked…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" />
                        </x-slot>
                        @foreach ($this->vendors as $v)
                            <flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Search customer…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Name or phone…" />
                        </x-slot>
                        @foreach ($this->customers as $c)
                            <flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration no…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search reg no…" />
                        </x-slot>
                        @foreach ($this->vehicles as $veh)
                            <flux:select.option :value="$veh->id" wire:key="vh-{{ $veh->id }}">{{ $veh->registration_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- PARTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Parts Requested</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each line is a part — pick a spare (brand/UOM/HSN/tax/rate auto-fill) or type a free-text description.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add part</flux:button>
                </div>

                @foreach ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end">
                            <flux:select wire:model.live="items.{{ $i }}.spare_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Spare" placeholder="Pick from catalogue (optional)…">
                                <x-slot name="search">
                                    <flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.spareSearch" placeholder="Search spare / part no…" />
                                </x-slot>
                                @foreach ($this->spareOptions($i) as $sp)
                                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $i }}-{{ $sp->id }}">{{ $sp->name }} <span class="text-zinc-400">({{ $sp->spare_code }})</span></flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>

                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Part description (required)" required />
                        <flux:error name="items.{{ $i }}.description" />

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.spare_brand_id" variant="listbox" size="sm" searchable clearable label="Brand" placeholder="Brand…">
                                @foreach ($this->spareBrands as $b)
                                    <flux:select.option :value="$b->id" wire:key="sb-{{ $i }}-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.part_type_id" variant="listbox" size="sm" clearable label="Inventory Type" placeholder="Genuine / Aftermarket…">
                                @foreach ($this->partTypes as $pt)
                                    <flux:select.option :value="$pt->id" wire:key="pt-{{ $i }}-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                @foreach ($this->uoms as $u)
                                    <flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax" placeholder="GST…">
                                @foreach ($this->taxes as $tx)
                                    <flux:select.option :value="$tx->id" wire:key="tx-{{ $i }}-{{ $tx->id }}">{{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" required />
                            <flux:input.group label="Rate (before tax)">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.rate_before_tax" type="number" step="0.01" min="0" size="sm" placeholder="Store quote" class:input="text-right font-mono" />
                            </flux:input.group>
                            <flux:select wire:model="items.{{ $i }}.stock_status" variant="listbox" size="sm" clearable label="Stock Status" placeholder="Availability…">
                                @foreach ($ITEM::stockStatuses() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.alternative_option" variant="listbox" size="sm" clearable label="Option" placeholder="Primary…">
                                @foreach ($ITEM::alternativeOptions() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- TAT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Timing</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">When it was asked and how fast it's needed.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:date-picker wire:model="requested_at" label="Requested At" placeholder="Optional" with-today selectable-header fixed-weeks type="input" required />
                    <flux:date-picker wire:model="needed_by" label="Needed By" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                </div>
                <flux:error name="needed_by" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="tat_option" variant="listbox" clearable label="Turnaround (TAT)" placeholder="Expected turnaround">
                        @foreach ($IPI::tatOptions() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.tat_option === 'custom'" x-cloak>
                        <flux:input wire:model="tat_custom_days" type="number" min="1" max="365" label="Custom TAT (days)" />
                    </div>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Photos & Attachments</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Evidence photos (before/after, damage, fault) and documents (PDF/image).</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                </div>

                @forelse ($attachments as $i => $att)
                    <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:select wire:model="attachments.{{ $i }}.photo_type_id" variant="listbox" size="sm" searchable clearable label="Type" placeholder="Evidence / document type…">
                            @foreach ($this->photoTypes as $pt)
                                <flux:select.option :value="$pt->id" wire:key="apt-{{ $i }}-{{ $pt->id }}">{{ $pt->group }} · {{ $pt->name }}</flux:select.option>
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
                        No files yet. Click <span class="font-medium">Add file</span> to attach a photo or PDF.
                    </div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Inquiry lifecycle.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <flux:select wire:model.live="status" variant="listbox" label="Inquiry Status" required>
                    @foreach ($IPI::statuses() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div x-show="$wire.status === 'not_available'" x-cloak>
                    <flux:select wire:model="rejection_reason_id" variant="listbox" clearable label="Rejection Reason" placeholder="Why not available">
                        @foreach ($this->rejectionReasons as $rr)
                            <flux:select.option :value="$rr->id" wire:key="rr-{{ $rr->id }}">{{ $rr->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="rejection_reason_id" />
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the store/advisor should know." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('internal-parts-inquiry.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create inquiry' }}</flux:button>
        </div>
    </form>
</div>
