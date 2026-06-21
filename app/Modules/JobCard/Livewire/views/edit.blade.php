<div>
    <form wire:submit="save" class="max-w-5xl">
        <div class="mb-8 flex items-start justify-between gap-4">
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

        {{-- CUSTOMER & VEHICLE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Customer & Vehicle</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who's bringing the vehicle, and which one.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:select wire:model.live="customer_id" variant="listbox" searchable label="Customer" placeholder="Pick a customer…" required>
                    @foreach ($this->customers as $c)
                        <flux:select.option :value="$c->id" wire:key="cust-{{ $c->id }}">
                            {{ trim($c->first_name.' '.($c->last_name ?? '')) }}{{ $c->phone ? ' · '.$c->phone : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable label="Vehicle" :placeholder="$customer_id ? 'Pick a vehicle…' : 'Pick a customer first'" :disabled="! $customer_id" required>
                    @foreach ($this->customerVehicles as $v)
                        <flux:select.option :value="$v['id']" wire:key="cv-{{ $v['id'] }}">{{ $v['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="customer_vehicle_id" />
            </div>
        </section>

        <flux:separator />

        {{-- TIMING & ROUTING --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Timing & Routing</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">When the vehicle arrived, when it's promised back, and who owns it.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:date-picker wire:model="opened_date" label="Opened Date" placeholder="Today" with-today selectable-header fixed-weeks type="input" />
                    <flux:time-picker wire:model="opened_time" label="Opened Time" placeholder="Now" type="input" />
                    <flux:date-picker wire:model="promised_date" label="Promised Date" placeholder="Tomorrow" with-today selectable-header fixed-weeks type="input" />
                    <flux:time-picker wire:model="promised_time" label="Promised Time" type="input" />
                    <flux:date-picker wire:model="expected_completion_date" label="Expected Completion Date" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                    <flux:time-picker wire:model="expected_completion_time" label="Expected Completion Time" type="input" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="workshop_department_id" variant="listbox" searchable label="Department" placeholder="Pick a department…" required>
                        @foreach ($this->workshopDepartments as $d)
                            <flux:select.option :value="$d->id" wire:key="wd-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="assigned_advisor_id" variant="listbox" searchable label="Advisor" placeholder="Pick an advisor…" required>
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="assigned_technician_id" variant="listbox" searchable clearable label="Technician" placeholder="Auto-assign later or pick now…">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="tech-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="service_type_id" variant="listbox" searchable clearable label="Service Type" placeholder="Pick a service type…">
                        @foreach ($this->serviceTypes as $st)
                            <flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="service_package_id" variant="listbox" searchable clearable label="Service Package" placeholder="None / Combo / AMC">
                        @foreach ($this->servicePackages as $sp)
                            <flux:select.option :value="$sp->id" wire:key="sp-{{ $sp->id }}">{{ $sp->name }}{{ $sp->is_amc ? ' (AMC)' : '' }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="status" variant="listbox" label="Status" required>
                        @foreach (\App\Modules\JobCard\Models\JobCard::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="current_stage_id" variant="listbox" searchable clearable label="Stage" placeholder="Lifecycle stage…">
                        @foreach ($this->jobStages as $stage)
                            <flux:select.option :value="$stage->id" wire:key="stg-{{ $stage->id }}">{{ $stage->name }} ({{ ucfirst($stage->track) }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="pending_reason_id" variant="listbox" searchable clearable label="Pending Reason" placeholder="If on hold…">
                        @foreach ($this->pendingReasons as $pr)
                            <flux:select.option :value="$pr->id" wire:key="pr-{{ $pr->id }}">{{ $pr->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- VEHICLE STATE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
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

        <flux:separator />

        {{-- COMPLAINTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Customer Complaints</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What the customer reported. One row per distinct complaint.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addComplaint">Add complaint</flux:button>
                </div>

                @if (count($complaints) === 0)
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No complaints captured yet. Click <span class="font-medium">Add complaint</span> to start.
                    </div>
                @else
                    @foreach ($complaints as $i => $row)
                        <div wire:key="complaint-row-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_180px_120px_40px] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <flux:textarea
                                wire:model="complaints.{{ $i }}.description"
                                size="sm"
                                label="Complaint"
                                placeholder="e.g. Brake making grinding noise on left turn"
                                rows="2"
                                required
                            />
                            <flux:select wire:model="complaints.{{ $i }}.complaint_type_id" variant="listbox" searchable clearable size="sm" label="Type">
                                @foreach ($this->complaintTypes as $ct)
                                    <flux:select.option :value="$ct->id" wire:key="ct-{{ $i }}-{{ $ct->id }}">{{ $ct->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="complaints.{{ $i }}.severity" variant="listbox" size="sm" label="Severity">
                                <flux:select.option value="low">Low</flux:select.option>
                                <flux:select.option value="medium">Medium</flux:select.option>
                                <flux:select.option value="high">High</flux:select.option>
                            </flux:select>
                            <flux:button
                                type="button"
                                size="sm"
                                variant="ghost"
                                icon="x-mark"
                                wire:click="removeComplaint({{ $i }})"
                                class="h-9!"
                            />
                        </div>
                        <flux:error name="complaints.{{ $i }}.description" />
                    @endforeach
                @endif
            </div>
        </section>

        <flux:separator />

        {{-- REQUESTED REPAIRS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
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

        <flux:separator />

        {{-- VEHICLE INVENTORY SNAPSHOT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
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

        <flux:separator />

        {{-- PHOTOS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
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

                                                    {{-- Retake + Remove — always visible so they're discoverable on touch --}}
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
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
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
                    <div class="mt-4 flex flex-col gap-2">
                        @foreach ($extras as $photo)
                            <flux:file-item
                                wire:key="extra-{{ $photo->id }}"
                                :heading="$photo->original_name ?? 'Photo #'.$photo->id"
                                :image="\Illuminate\Support\Facades\Storage::disk('public')->url($photo->path)"
                                :size="$photo->size_bytes ?? 0"
                            >
                                <x-slot name="actions">
                                    <flux:file-item.remove wire:click="removeExistingPhoto({{ $photo->id }})" />
                                </x-slot>
                            </flux:file-item>
                        @endforeach
                        @foreach ($extraFiles as $i => $file)
                            <flux:file-item
                                wire:key="extra-staged-{{ $i }}"
                                :heading="$file->getClientOriginalName()"
                                :image="$file->temporaryUrl()"
                                :size="$file->getSize()"
                            >
                                <x-slot name="actions">
                                    <flux:file-item.remove wire:click="removeExtraFile({{ $i }})" />
                                </x-slot>
                            </flux:file-item>
                        @endforeach
                    </div>
                @endif
                <flux:error name="extraFiles" />
                <flux:error name="extraFiles.*" />
            </div>
        </section>

        <flux:separator />

        {{-- ADVISOR NOTES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
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

        {{-- T&Cs --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
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

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('job-card.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Create Job Card' }}</flux:button>
        </div>
    </form>
</div>
