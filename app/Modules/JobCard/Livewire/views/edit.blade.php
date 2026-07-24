<div class="max-w-7xl">
    {{-- HEADER --}}
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('job-card.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Job Cards
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? 'Job Card '.$job_card_no : 'New Job Card' }}
                </flux:heading>
                @if ($appointment_id)
                    <flux:text size="sm" class="mt-1 text-zinc-500">Linked to Appointment #{{ $appointment_id }}</flux:text>
                @endif
            </div>
            @if ($editingId)
                <div class="flex items-center gap-2">
                    @if ($this->vehicleJobCards->isNotEmpty())
                        <flux:modal.trigger name="vehicle-history">
                            <flux:button type="button" size="sm" variant="ghost" icon="truck">
                                Vehicle History ({{ $this->vehicleJobCards->count() }})
                            </flux:button>
                        </flux:modal.trigger>
                    @endif
                    <flux:dropdown>
                        <flux:button size="sm" variant="ghost" icon="magnifying-glass-circle" icon:trailing="chevron-down">
                            Inspection @if ($this->digitalInspections->isNotEmpty())({{ $this->digitalInspections->count() }})@endif
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item icon="plus" :href="route('digital-inspection.create', ['from-job-card' => $editingId])" wire:navigate>
                                New inspection
                            </flux:menu.item>
                            @if ($this->digitalInspections->isNotEmpty())
                                <flux:menu.separator />
                                @foreach ($this->digitalInspections as $di)
                                    <flux:menu.item :href="route('digital-inspection.edit', $di->id)" wire:navigate>
                                        {{ $di->inspection_no }} · {{ \App\Modules\DigitalInspection\Models\DigitalInspection::statuses()[$di->status] ?? $di->status }}
                                    </flux:menu.item>
                                @endforeach
                            @endif
                        </flux:menu>
                    </flux:dropdown>
                    <flux:button :href="route('job-history.show', $editingId)" wire:navigate size="sm" variant="ghost" icon="clock">History</flux:button>
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
        <form wire:submit="save" class="min-w-0">
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
                    @include('job-card::partials.section-timing', ['lean' => false])
                    <flux:separator />

                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Insurance &amp; Authorisation</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">For insurance jobs, outside-vendor work, and how the customer authorised the repair.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="For insurance jobs…">
                                    @foreach ($this->insuranceCompanies as $ic)
                                        <flux:select.option :value="$ic->id" wire:key="ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="policy_no" label="Policy No." placeholder="Insurance policy number" class:input="font-mono uppercase" />
                                <flux:select wire:model="vendor_id" variant="listbox" searchable clearable label="Vendor" placeholder="Outside / parts vendor…" :filter="false">
                                <x-slot name="search">
                                    <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Type a vendor name or code…" />
                                </x-slot>
                                    @foreach ($this->vendors as $v)
                                        <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="customer_approval_type_id" variant="listbox" searchable clearable label="Customer Approval Option" placeholder="How approval was taken…">
                                    @foreach ($this->customerApprovalTypes as $ca)
                                        <flux:select.option :value="$ca->id" wire:key="ca-{{ $ca->id }}">{{ $ca->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        </div>
                    </section>

                    <flux:separator />

                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
                        <div>
                            <flux:heading size="lg">Vehicle State at Receipt</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Snapshot of the vehicle's odometer and fuel level when received.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:input.group label="Odometer (km)">
                                    <flux:input wire:model="km_at_service" type="number" min="0" placeholder="45000" class:input="text-right font-mono" />
                                    <flux:input.group.suffix>km</flux:input.group.suffix>
                                </flux:input.group>
                                <flux:select wire:model="fuel_level" variant="listbox" clearable label="Fuel Level" placeholder="—">
                                    @foreach (\App\Modules\JobCard\Models\JobCard::fuelLevels() as $key => $label)
                                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        </div>
                    </section>
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
                            <flux:select wire:model="requestedRepairIds" variant="listbox" multiple searchable placeholder="Pick requested repairs…" clearable>
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
                    <flux:heading size="lg">Vehicle History</flux:heading>
                    <flux:text size="sm" class="mt-1 text-zinc-500">Other job cards for this vehicle. Each opens in a new tab.</flux:text>
                </div>

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
                                <span>{{ $vjc->opened_at?->format('d M Y') ?? '—' }}</span>
                                @if ($vjc->currentStage)<span>· {{ $vjc->currentStage->name }}</span>@endif
                                @if ($vjc->workshopDepartment)<span>· {{ $vjc->workshopDepartment->name }}</span>@endif
                                @if ($vjc->advisor)<span>· {{ $vjc->advisor->name }}</span>@endif
                                @if ($vjc->km_at_service)<span>· {{ number_format($vjc->km_at_service) }} km</span>@endif
                            </div>
                        </a>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-8 text-center text-sm text-zinc-500">
                            No other job cards for this vehicle yet.
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
</div>
