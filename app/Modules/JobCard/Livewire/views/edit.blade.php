<div class="max-w-7xl">
    {{-- HEADER --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <flux:link :href="route('job-card.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Job Cards
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1 whitespace-nowrap">
                    {{ $editingId ? 'Job Card '.$job_card_no : 'New Job Card' }}
                </flux:heading>
                @if ($appointment_id)
                    <flux:text size="sm" class="mt-1 text-zinc-500">Linked to Appointment #{{ $appointment_id }}</flux:text>
                @endif
            </div>
            @if ($editingId)
                {{-- Three controls, not ten: what you can create, where you can go,
                     and where the card stands. Everything else lives inside them. --}}
                <div class="flex shrink-0 items-center gap-2">
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="primary" icon="plus" icon:trailing="chevron-down">Create</flux:button>
                        <flux:menu>
                            <flux:menu.item icon="magnifying-glass-circle" :href="route('digital-inspection.create', ['from-job-card' => $editingId])" wire:navigate>
                                Inspection
                            </flux:menu.item>
                            @can('vehicle_inspection_order.create')
                                <flux:menu.item icon="clipboard-document-check" :href="route('vehicle-inspection-order.create', ['from-job-card' => $editingId])" wire:navigate>
                                    Work order
                                </flux:menu.item>
                            @endcan
                            @can('internal_parts_inquiry.create')
                                <flux:menu.item icon="cube" :href="route('internal-parts-inquiry.create', ['from-job-card' => $editingId])" wire:navigate>
                                    Part inquiry
                                </flux:menu.item>
                            @endcan
                            @can('pickup_drop.create')
                                <flux:menu.item icon="map-pin" :href="route('pickup-drop.create', ['from-job-card' => $editingId])" wire:navigate>
                                    Pickup / drop
                                </flux:menu.item>
                            @endcan
                        </flux:menu>
                    </flux:dropdown>

                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" icon:trailing="chevron-down">Go to</flux:button>
                        <flux:menu>
                            <flux:menu.item icon="clock" :href="route('job-history.show', $editingId)" wire:navigate>
                                Job history
                            </flux:menu.item>
                            @if ($customer_vehicle_id)
                                @can('job_history.view')
                                    <flux:menu.item icon="truck" :href="route('job-history.vehicle-timeline', $customer_vehicle_id)" wire:navigate>
                                        Vehicle timeline
                                    </flux:menu.item>
                                @endcan
                            @endif
                            @can('technician_finding.view')
                                <flux:menu.item icon="clipboard-document-check" :href="route('technician-finding.report', $editingId)" wire:navigate>
                                    Findings report
                                </flux:menu.item>
                            @endcan
                            <flux:menu.item icon="wrench-screwdriver" x-on:click="$flux.modal('vehicle-history').show()">
                                Service history @if ($this->vehicleJobCards->isNotEmpty())({{ $this->vehicleJobCards->count() }})@endif
                            </flux:menu.item>
                            <flux:menu.item icon="squares-2x2" x-on:click="$flux.modal('record-panel').show()">
                                Related areas
                            </flux:menu.item>

                            @if ($this->digitalInspections->isNotEmpty() || $this->inspectionOrders->isNotEmpty())
                                <flux:menu.separator />
                                @foreach ($this->digitalInspections as $di)
                                    <flux:menu.item :href="route('digital-inspection.edit', $di->id)" wire:navigate>
                                        {{ $di->inspection_no }} · {{ \App\Modules\DigitalInspection\Models\DigitalInspection::statuses()[$di->status] ?? $di->status }}
                                    </flux:menu.item>
                                @endforeach
                                @foreach ($this->inspectionOrders as $vio)
                                    <flux:menu.item :href="route('vehicle-inspection-order.edit', $vio->id)" wire:navigate>
                                        {{ $vio->order_no }} · {{ \App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder::statuses()[$vio->status] ?? $vio->status }}
                                    </flux:menu.item>
                                @endforeach
                            @endif
                        </flux:menu>
                    </flux:dropdown>

                    <flux:badge :color="match ($status) {
                        'open' => 'amber', 'in_progress' => 'blue', 'awaiting_parts' => 'sky',
                        'awaiting_approval' => 'purple', 'completed' => 'lime', 'closed' => 'zinc',
                        'cancelled' => 'red', default => 'zinc',
                    }" size="lg">{{ \App\Modules\JobCard\Models\JobCard::statuses()[$status] }}</flux:badge>
                </div>
            @endif
        </div>

        <flux:separator />

        <div class="grid lg:grid-cols-[minmax(0,1fr)_340px] gap-8 mt-6">
        <form wire:submit="save" novalidate class="min-w-0">
        @if (! $editingId)
            {{-- ============ LEAN CREATE ============ --}}
            @include('job-card::partials.section-customer-vehicle')
            <flux:separator />
            @include('job-card::partials.section-timing', ['lean' => true])
            <flux:separator />
            @include('job-card::partials.section-complaints')

            <div class="mt-6 flex items-start gap-3 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-sm text-zinc-500">
                <flux:icon.lock-closed class="size-5 shrink-0 text-zinc-400" />
                <span>Vehicle inventory, photos, inspection sign-off, the stage tree and vehicle history unlock once you create the card — you'll land straight on the full editor.</span>
            </div>
        @else
            {{-- ============ RICH EDIT — TABS ============ --}}
            <flux:tab.group class="mt-6">
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="clipboard-document-list">Details</flux:tab>
                    <flux:tab name="complaints" icon="chat-bubble-left-right">Complaints &amp; Repairs</flux:tab>
                    <flux:tab name="inventory" icon="archive-box">Inventory</flux:tab>
                    <flux:tab name="photos" icon="camera">Photos</flux:tab>
                    <flux:tab name="closeout" icon="check-badge">Close-out</flux:tab>
                </flux:tabs>

                {{-- DETAILS --}}
                <flux:tab.panel name="details">
                    @include('job-card::partials.section-customer-vehicle')
                    <flux:separator />

                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Vehicle State at Receipt</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Snapshot of the vehicle's odometer and fuel level when received.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {{-- The last reading sits in the label: "45,000 km"
                                     means nothing without what it was last time. --}}
                                <flux:input.group :label="$this->lastServiceKm
                                    ? 'Odometer In (km) · last service '.number_format($this->lastServiceKm).' km'
                                    : 'Odometer In (km)'">
                                    <flux:input wire:model="km_at_service" type="number" min="0"
                                        :placeholder="$this->lastServiceKm ? number_format($this->lastServiceKm) : '45000'"
                                        class:input="text-right font-mono" />
                                    <flux:input.group.suffix>km</flux:input.group.suffix>
                                </flux:input.group>
                                <flux:input.group label="Odometer Out (km)">
                                    <flux:input wire:model="odometer_out" type="number" min="0" placeholder="—" class:input="text-right font-mono" />
                                    <flux:input.group.suffix>km</flux:input.group.suffix>
                                </flux:input.group>
                                <flux:select wire:model="fuel_level" variant="listbox" clearable label="Fuel Level" placeholder="—">
                                    @foreach (\App\Modules\JobCard\Models\JobCard::fuelLevels() as $key => $label)
                                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="brought_by" variant="listbox" clearable label="Brought By" placeholder="Owner / Driver…">
                                    @foreach (\App\Modules\JobCard\Models\JobCard::broughtByOptions() as $key => $label)
                                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input.group label="Avg. Mileage">
                                    <flux:input wire:model="avg_mileage" type="number" min="0" placeholder="—" class:input="text-right font-mono" />
                                    <flux:input.group.suffix>km/l</flux:input.group.suffix>
                                </flux:input.group>
                                @if ($legacy_bill_no)
                                    <flux:input label="Billed Under (legacy)" wire:model="legacy_bill_no" readonly class:input="font-mono" />
                                @endif
                            </div>
                        </div>
                    </section>

                    <flux:separator />
                    @include('job-card::partials.section-timing', ['lean' => false])
                    <flux:separator />

                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Insurance &amp; Authorisation</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Who does the work — in-house or out — and, for bodyshop jobs, who is paying for it.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            {{-- Insurance is a bodyshop concern; a service card never
                                 needs an insurer, so it is not asked for. --}}
                            @if ($this->isBodyshopDepartment)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="For insurance jobs…">
                                        @foreach ($this->insuranceCompanies as $ic)
                                            <flux:select.option :value="$ic->id" wire:key="ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:input wire:model="policy_no" label="Policy No." placeholder="Insurance policy number" class:input="font-mono uppercase" />
                                </div>
                            @endif

                            {{-- One or the other: the job is done in-house by a
                                 technician, or sent out to a contractor. Picking
                                 either side clears the other. --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:select wire:model.live="assigned_technician_id" variant="listbox" searchable clearable
                                    label="In-house Technician"
                                    :placeholder="$workshop_department_id ? 'Assign a technician…' : 'Pick a department first'"
                                    :disabled="! $workshop_department_id || (bool) $vendor_id">
                                    @foreach ($this->employeesByDepartment['technicians'] as $e)
                                        <flux:select.option :value="$e->id" wire:key="tech2-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:select wire:model.live="vendor_id" variant="listbox" searchable clearable :filter="false"
                                    label="Service Contractor / Outside Labour"
                                    :placeholder="$assigned_technician_id ? 'Doing it in-house' : 'Send the work out…'"
                                    :disabled="(bool) $assigned_technician_id">
                                    <x-slot name="search">
                                        <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Contractor or vendor name…" />
                                    </x-slot>
                                    @foreach ($this->outsideVendors as $v)
                                        <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <flux:text size="sm" class="text-zinc-500">
                                Either an in-house technician or an outside contractor — not both.
                            </flux:text>

                            {{-- The customer's signature IS the approval record; a
                                 dropdown saying how they approved proved nothing. --}}
                            <flux:field>
                                <flux:label>Customer Approval</flux:label>
                                @php $sig = $this->existingSignaturePath(); @endphp
                                @if ($sig && ! $clearSignature)
                                    <div class="flex items-center gap-3">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($sig) }}" alt="Customer signature"
                                            class="h-16 w-auto rounded border border-zinc-200 dark:border-zinc-800 bg-white p-1" />
                                        <flux:badge color="lime" size="sm">Signed</flux:badge>
                                        <flux:button size="xs" variant="ghost" wire:click="markClearSignature">Replace</flux:button>
                                    </div>
                                @else
                                    <flux:file-upload wire:model="signatureUpload" accept="image/*" />
                                    <flux:description>The signature on the printed job card is the authorisation — capture or photograph it here.</flux:description>
                                @endif
                                <flux:error name="signatureUpload" />
                            </flux:field>
                        </div>
                    </section>

                    <flux:separator />

                    
                </flux:tab.panel>

                {{-- COMPLAINTS & REPAIRS --}}
                <flux:tab.panel name="complaints">
                    @include('job-card::partials.section-complaints')
                    <flux:separator />

                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Requested Repairs</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Specific miscellaneous jobs the customer asked for, beyond the complaints above.</flux:text>
                        </div>
                        <div class="space-y-2 min-w-0">
                            <flux:select wire:model="requestedRepairIds" variant="listbox" multiple searchable clearable
                                :placeholder="$workshop_department_id ? 'Pick requested repairs…' : 'Pick a department first'"
                                :disabled="! $workshop_department_id">
                                @foreach ($this->requestedRepairOptions as $rr)
                                    <flux:select.option :value="$rr->id" wire:key="rr-{{ $rr->id }}">{{ $rr->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </section>
                </flux:tab.panel>

                {{-- INVENTORY --}}
                <flux:tab.panel name="inventory">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Vehicle Inventory</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Every item defaults to <span class="font-medium">Present</span>. Flag anything <span class="font-medium">Missing</span> or <span class="font-medium">Damaged</span> — tag the damage and add a note.</flux:text>
                        </div>
                        <div class="space-y-2 min-w-0">
                            @if ($this->inventoryChecklist->isEmpty())
                                <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                                    No vehicle inventory items configured. Add them in <span class="font-medium">Workshop → Vehicle Inventory Items</span>.
                                </div>
                            @else
                                @foreach ($this->inventoryChecklist as $item)
                                    @php $status = data_get($inventoryItems, $item->id.'.status', 'present'); @endphp
                                    <div wire:key="inv-{{ $item->id }}" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-center py-2 border-b border-zinc-100 dark:border-zinc-800/60 last:border-0">
                                        <div class="text-sm font-medium">{{ $item->name }}</div>
                                        <flux:radio.group wire:model.live="inventoryItems.{{ $item->id }}.status" variant="segmented" size="sm">
                                            <flux:radio value="present" label="Present" />
                                            <flux:radio value="missing" label="Missing" />
                                            <flux:radio value="damaged" label="Damaged" />
                                        </flux:radio.group>

                                        @if ($status !== 'present')
                                            <div class="md:col-span-2 grid grid-cols-1 {{ $status === 'damaged' ? 'md:grid-cols-[200px_1fr]' : '' }} gap-2 pl-1">
                                                @if ($status === 'damaged')
                                                    <flux:select wire:model="inventoryItems.{{ $item->id }}.damage_type_id" variant="listbox" searchable clearable size="sm" placeholder="Damage type…">
                                                        @foreach ($this->damageTypes as $dt)
                                                            <flux:select.option :value="$dt->id" wire:key="inv-dt-{{ $item->id }}-{{ $dt->id }}">{{ $dt->name }}</flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                @endif
                                                <flux:input
                                                    wire:model="inventoryItems.{{ $item->id }}.condition_notes"
                                                    size="sm"
                                                    placeholder="{{ $status === 'missing' ? 'Note what is missing (optional)' : 'Describe the damage (optional)' }}"
                                                />
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </section>
                </flux:tab.panel>

                {{-- PHOTOS --}}
                <flux:tab.panel name="photos">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Vehicle Photos</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Walk each tab and capture every defined angle — a full photo record of the car's condition at intake. Tap a slot to take or retake its photo.</flux:text>
                            @php $prog = $this->photoProgress(); @endphp
                            @if ($prog['total'] > 0)
                                <flux:badge :color="$prog['captured'] === $prog['total'] ? 'lime' : 'amber'" size="sm" class="mt-3">
                                    {{ $prog['captured'] }} / {{ $prog['total'] }} key photos captured
                                </flux:badge>
                            @endif
                        </div>
                        <div class="space-y-4 min-w-0">
                            @if ($this->photoGroups->isEmpty())
                                <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                                    No photo slots configured. Define them in <span class="font-medium">Workshop → Photo Types</span> (group them into tabs like Exterior, Interior, Documents).
                                </div>
                            @else
                                <flux:tab.group>
                                    <flux:tabs scrollable>
                                        @foreach ($this->photoGroups as $group)
                                            <flux:tab name="{{ $group['key'] }}" wire:key="tab-{{ $group['key'] }}">
                                                {{ $group['label'] }} <span class="ml-1 text-xs text-zinc-400">{{ $this->groupProgress($group['slots']) }}</span>
                                            </flux:tab>
                                        @endforeach
                                    </flux:tabs>

                                    @foreach ($this->photoGroups as $group)
                                        <flux:tab.panel name="{{ $group['key'] }}" wire:key="panel-{{ $group['key'] }}">
                                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                                @foreach ($group['slots'] as $slot)
                                                    @php
                                                        $existing = $this->slotPhoto($slot->id);
                                                        $staged = $slotFiles[$slot->id] ?? null;
                                                        $url = $staged ? $staged->temporaryUrl() : ($existing ? \Illuminate\Support\Facades\Storage::disk('public')->url($existing->path) : null);
                                                    @endphp
                                                    <div wire:key="slot-{{ $slot->id }}" class="relative rounded-lg border {{ $url ? 'border-zinc-200 dark:border-zinc-700' : 'border-dashed border-zinc-300 dark:border-zinc-700' }} overflow-hidden">
                                                        @if ($url)
                                                            <div class="relative aspect-square">
                                                                <img src="{{ $url }}" alt="{{ $slot->name }}" class="size-full object-cover" />
                                                                <flux:badge color="lime" size="sm" icon="check" class="absolute top-1 left-1" />
                                                                <div class="absolute inset-x-0 bottom-0 flex divide-x divide-white/20">
                                                                    <label class="flex flex-1 cursor-pointer items-center justify-center gap-1 bg-black/55 py-1.5 text-xs font-medium text-white hover:bg-black/70">
                                                                        <input type="file" class="sr-only" wire:model="slotFiles.{{ $slot->id }}" accept="image/*" capture="environment" />
                                                                        <flux:icon.arrow-path variant="micro" class="size-3.5" />
                                                                        Retake
                                                                    </label>
                                                                    <button type="button"
                                                                        wire:click="{{ $staged ? 'clearSlotFile('.$slot->id.')' : 'removeExistingPhoto('.$existing->id.')' }}"
                                                                        class="flex flex-1 items-center justify-center gap-1 bg-black/55 py-1.5 text-xs font-medium text-white hover:bg-red-600/80">
                                                                        <flux:icon.trash variant="micro" class="size-3.5" />
                                                                        Remove
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <label class="block aspect-square cursor-pointer">
                                                                <input type="file" class="sr-only" wire:model="slotFiles.{{ $slot->id }}" accept="image/*" capture="environment" />
                                                                <div class="flex size-full flex-col items-center justify-center gap-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                                                    <flux:icon.camera class="size-7" />
                                                                    <span class="text-[11px] font-medium">Tap to capture</span>
                                                                </div>
                                                            </label>
                                                        @endif

                                                        <div class="px-2 py-1.5">
                                                            <span class="truncate text-xs font-medium">{{ $slot->name }}</span>
                                                        </div>
                                                        <flux:error name="slotFiles.{{ $slot->id }}" />
                                                    </div>
                                                @endforeach
                                            </div>
                                        </flux:tab.panel>
                                    @endforeach
                                </flux:tab.group>
                            @endif
                        </div>
                    </section>

                    <flux:separator />

                    {{-- ADDITIONAL / DAMAGE PHOTOS --}}
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Additional / Damage Photos</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Any extra shots beyond the standard angles — close-ups of dents, scratches, existing damage or anything worth recording.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <flux:file-upload wire:model="extraFiles" multiple accept="image/*">
                                <flux:file-upload.dropzone
                                    heading="Drop files here or click to browse"
                                    text="JPG, PNG up to 8MB"
                                />
                            </flux:file-upload>

                            @php $extras = $this->extraPhotos(); @endphp
                            @if ($extras->isNotEmpty() || count($extraFiles) > 0)
                                <div class="mt-4 flex flex-col gap-3">
                                    @foreach ($extras as $photo)
                                        <div wire:key="extra-{{ $photo->id }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 space-y-3">
                                            <flux:file-item
                                                :heading="$photo->original_name ?? 'Photo #'.$photo->id"
                                                :image="\Illuminate\Support\Facades\Storage::disk('public')->url($photo->path)"
                                                :size="$photo->size_bytes ?? 0"
                                            >
                                                <x-slot name="actions">
                                                    <flux:file-item.remove wire:click="removeExistingPhoto({{ $photo->id }})" />
                                                </x-slot>
                                            </flux:file-item>
                                            <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-2">
                                                <flux:select wire:model="existingPhotoMeta.{{ $photo->id }}.damage_type_id" variant="listbox" searchable clearable size="sm" placeholder="Damage type…">
                                                    @foreach ($this->damageTypes as $dt)
                                                        <flux:select.option :value="$dt->id" wire:key="ex-dt-{{ $photo->id }}-{{ $dt->id }}">{{ $dt->name }}</flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                                <flux:input wire:model="existingPhotoMeta.{{ $photo->id }}.location_note" size="sm" placeholder="Location on vehicle (e.g. front-left bumper)" />
                                            </div>
                                        </div>
                                    @endforeach
                                    @foreach ($extraFiles as $i => $file)
                                        <div wire:key="extra-staged-{{ $i }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 space-y-3">
                                            <flux:file-item
                                                :heading="$file->getClientOriginalName()"
                                                :image="$file->temporaryUrl()"
                                                :size="$file->getSize()"
                                            >
                                                <x-slot name="actions">
                                                    <flux:file-item.remove wire:click="removeExtraFile({{ $i }})" />
                                                </x-slot>
                                            </flux:file-item>
                                            <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-2">
                                                <flux:select wire:model="extraDamageTypes.{{ $i }}" variant="listbox" searchable clearable size="sm" placeholder="Damage type…">
                                                    @foreach ($this->damageTypes as $dt)
                                                        <flux:select.option :value="$dt->id" wire:key="st-dt-{{ $i }}-{{ $dt->id }}">{{ $dt->name }}</flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                                <flux:input wire:model="extraLocations.{{ $i }}" size="sm" placeholder="Location on vehicle (e.g. front-left bumper)" />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <flux:error name="extraFiles" />
                            <flux:error name="extraFiles.*" />
                        </div>
                    </section>
                </flux:tab.panel>

                {{-- CLOSE-OUT --}}
                <flux:tab.panel name="closeout">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Advisor Notes</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Suggested services and any internal notes for the technician.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <flux:textarea
                                wire:model="suggested_services"
                                label="Suggested Services"
                                placeholder="e.g. Recommended brake fluid change due to age + alignment check."
                                rows="3"
                            />
                            <flux:textarea
                                wire:model="additional_work"
                                label="Additional / Other Work"
                                placeholder="Extra repairs beyond the standard list (e.g. wheel balancing 17&quot; alloy, gum removing)."
                                rows="2"
                            />
                            <flux:textarea
                                wire:model="notes"
                                label="Internal Notes"
                                placeholder="Optional notes for the technician or front desk."
                                rows="2"
                            />
                        </div>
                    </section>

                    <flux:separator />

                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Terms & Acceptance</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Customer's acceptance of the workshop's standard T&Cs, plus their signature on the printed job card.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <flux:switch
                                wire:model="terms_accepted"
                                label="Customer accepted T&Cs"
                                description="Stamps the acceptance time on save."
                            />

                            @php $existingSig = $this->existingSignaturePath(); @endphp
                            @if ($existingSig && ! $clearSignature)
                                <div class="space-y-2">
                                    <flux:text size="sm" class="text-zinc-500">Signature on file</flux:text>
                                    <div class="flex items-start gap-3">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingSig) }}" alt="Customer signature" class="h-20 w-auto rounded border border-zinc-200 dark:border-zinc-800 bg-white p-1" />
                                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="markClearSignature">Remove</flux:button>
                                    </div>
                                </div>
                            @endif

                            <flux:file-upload wire:model="signatureUpload" accept="image/*">
                                <flux:file-upload.dropzone
                                    icon="pencil-square"
                                    :heading="$existingSig && ! $clearSignature ? 'Replace signature' : 'Upload customer signature'"
                                    text="PNG / JPG up to 2 MB"
                                />
                            </flux:file-upload>

                            @if ($signatureUpload)
                                <div class="flex items-center gap-3">
                                    <img src="{{ $signatureUpload->temporaryUrl() }}" alt="" class="h-16 w-auto rounded border border-zinc-200 dark:border-zinc-800 bg-white p-1" />
                                    <flux:text size="sm" class="text-zinc-500">Preview — will be saved when you submit.</flux:text>
                                </div>
                            @endif
                            <flux:error name="signatureUpload" />
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>
        @endif

            {{-- STICKY ACTION BAR --}}
            <div class="sticky bottom-0 z-10 mt-8 flex items-center justify-end gap-2 border-t border-zinc-200 dark:border-zinc-800 bg-white/95 dark:bg-zinc-900/95 py-4 backdrop-blur">
                <flux:button :href="route('job-card.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Create Job Card' }}</flux:button>
            </div>
        </form>

        {{-- STICKY DETAILS PANEL --}}
        <aside class="hidden lg:block">
            <div class="sticky top-6 space-y-3">
                <flux:heading size="sm">Details</flux:heading>
                @include('job-card::partials.details-panel')
            </div>
        </aside>
        </div>

    {{-- VEHICLE HISTORY DRAWER --}}
    @if ($editingId)
        <flux:modal name="vehicle-history" variant="flyout" class="w-full max-w-lg">
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">Service History</flux:heading>
                    <flux:text size="sm" class="mt-1 text-zinc-500">
                        What this vehicle has had done, and when — search a service to see the visits that included it.
                    </flux:text>
                </div>

                {{-- WHAT'S BEEN DONE — the basis for recommending what's due --}}
                @if ($this->serviceHistory->isNotEmpty())
                    <div>
                        @php($servicesShown = (int) \App\Support\AppSettings::int('service_history.services_shown', 8))
                        <flux:text size="sm" class="mb-2 font-medium">Last done</flux:text>
                        <div class="space-y-1.5">
                            @foreach ($this->serviceHistory->take($servicesShown) as $s)
                                <button type="button" wire:click="$set('historySearch', @js($s['service']))"
                                    class="flex w-full items-center justify-between gap-3 rounded-md border px-3 py-2 text-left transition hover:border-mq-orange-500 {{ $s['is_due'] ? 'border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20' : 'border-zinc-200 dark:border-zinc-800' }}">
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm">{{ \Illuminate\Support\Str::limit($s['service'], 44) }}</span>
                                        @if ($s['interval'])
                                            <span class="block text-xs text-zinc-400">every {{ $s['interval'] }}</span>
                                        @endif
                                    </span>
                                    <span class="shrink-0 text-xs text-zinc-500">
                                        {{ $s['last_done_at']?->format('d/m/Y') ?? '—' }}
                                        @if ($s['last_km'])· {{ number_format($s['last_km']) }} km @endif
                                    </span>
                                    {{-- Says what is overdue and by how much, rather than just how old it is. --}}
                                    @if ($s['is_due'])
                                        <flux:badge size="sm" color="amber">Due · {{ $s['due_reason'] }}</flux:badge>
                                    @elseif ($s['times'] > 1)
                                        <flux:badge size="sm" color="zinc">×{{ $s['times'] }}</flux:badge>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <flux:input wire:model.live.debounce.300ms="historySearch" icon="magnifying-glass" clearable
                    placeholder="Filter visits by service — e.g. oil change" />

                <div class="space-y-2">
                    @forelse ($this->vehicleJobCards as $vjc)
                        <a href="{{ route('job-card.edit', $vjc->id) }}" target="_blank"
                            class="block rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 transition hover:border-mq-orange-500 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-sm font-medium">{{ $vjc->job_card_no }}</span>
                                <flux:badge :color="match ($vjc->status) {
                                    'open' => 'amber', 'in_progress' => 'blue', 'awaiting_parts' => 'sky',
                                    'awaiting_approval' => 'purple', 'completed' => 'lime', 'closed' => 'zinc',
                                    'cancelled' => 'red', default => 'zinc',
                                }" size="sm">{{ \App\Modules\JobCard\Models\JobCard::statuses()[$vjc->status] ?? $vjc->status }}</flux:badge>
                            </div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500">
                                <span>{{ $vjc->opened_at?->format('d/m/Y') ?? '—' }}</span>
                                @if ($vjc->currentStage)<span>· {{ $vjc->currentStage->name }}</span>@endif
                                @if ($vjc->workshopDepartment)<span>· {{ $vjc->workshopDepartment->name }}</span>@endif
                                @if ($vjc->advisor)<span>· {{ $vjc->advisor->name }}</span>@endif
                                @if ($vjc->km_at_service)<span>· {{ number_format($vjc->km_at_service) }} km</span>@endif
                            </div>

                            {{-- The actual work on that visit. --}}
                            @php($done = $vjc->complaints->pluck('description')->filter()->merge($vjc->requestedRepairs->pluck('name'))->unique()->take(4))
                            @if ($done->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($done as $d)
                                        <flux:badge size="sm" color="zinc">{{ \Illuminate\Support\Str::limit($d, 32) }}</flux:badge>
                                    @endforeach
                                </div>
                            @endif
                        </a>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-8 text-center text-sm text-zinc-500">
                            @if (trim($historySearch) !== '')
                                No past visit matched “{{ $historySearch }}”.
                                <button type="button" wire:click="$set('historySearch', '')" class="text-mq-orange-500 underline">Clear filter</button>
                            @else
                                No other job cards for this vehicle yet.
                            @endif
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Related-areas flyout --}}
    @if ($editingId)
        <livewire:record-panel subject="job_card" :record-id="$editingId" :record-label="$job_card_no" />
    @endif
</div>
