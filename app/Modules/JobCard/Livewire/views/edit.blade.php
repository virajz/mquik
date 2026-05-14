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
                <flux:badge :color="match ($status) {
                    'open' => 'amber', 'in_progress' => 'blue', 'awaiting_parts' => 'sky',
                    'awaiting_approval' => 'purple', 'completed' => 'lime', 'closed' => 'zinc',
                    'cancelled' => 'red', default => 'zinc',
                }" size="lg">{{ \App\Modules\JobCard\Models\JobCard::statuses()[$status] }}</flux:badge>
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

                <flux:select wire:model="status" variant="listbox" label="Status" class="md:max-w-xs" required>
                    @foreach (\App\Modules\JobCard\Models\JobCard::statuses() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
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

        {{-- VEHICLE INVENTORY SNAPSHOT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vehicle Inventory</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Tick off what's IN the car when received. Add notes for damaged or missing items.</flux:text>
            </div>
            <div class="space-y-2 min-w-0">
                @if ($this->inventoryChecklist->isEmpty())
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No vehicle inventory items configured. Add them in <span class="font-medium">Workshop → Vehicle Inventory Items</span>.
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2">
                        @foreach ($this->inventoryChecklist as $item)
                            <div wire:key="inv-{{ $item->id }}" class="flex items-start gap-3 py-2">
                                <flux:checkbox wire:model="inventoryItems.{{ $item->id }}.is_present" class="mt-1" />
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium">{{ $item->name }}</div>
                                    @if (data_get($inventoryItems, $item->id.'.is_present'))
                                        <flux:input
                                            wire:model="inventoryItems.{{ $item->id }}.condition_notes"
                                            size="sm"
                                            placeholder="Condition notes (optional)"
                                            class="mt-1"
                                        />
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <flux:separator />

        {{-- PHOTOS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Photos</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Pre-service photos of the vehicle — dents, scratches, panel condition, mileage display.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                @if ($this->existingPhotos->isNotEmpty())
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach ($this->existingPhotos as $photo)
                            @php $removed = in_array($photo->id, $removedPhotoIds, true); @endphp
                            <div wire:key="existing-photo-{{ $photo->id }}" class="relative rounded-md overflow-hidden border border-zinc-200 dark:border-zinc-800 {{ $removed ? 'opacity-40' : '' }}">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->path) }}" alt="{{ $photo->caption ?? $photo->original_name }}" class="aspect-square w-full object-cover" />
                                <div class="px-2 py-1 text-xs truncate">{{ $photo->caption ?? $photo->original_name }}</div>
                                <div class="absolute top-1 right-1">
                                    @if ($removed)
                                        <flux:button type="button" size="xs" variant="ghost" icon="arrow-uturn-left" wire:click="undoRemoveExistingPhoto({{ $photo->id }})" />
                                    @else
                                        <flux:button type="button" size="xs" variant="ghost" icon="trash" wire:click="removeExistingPhoto({{ $photo->id }})" />
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <flux:file-upload wire:model="newPhotos" multiple accept="image/*">
                    <flux:file-upload.dropzone>
                        <flux:icon.photo class="size-8 text-zinc-400" />
                        <span class="text-sm font-medium">Drop photos here or click to choose</span>
                        <flux:text size="xs" class="text-zinc-500">JPG / PNG · up to 8 MB each</flux:text>
                    </flux:file-upload.dropzone>
                </flux:file-upload>

                @if (count($newPhotos) > 0)
                    <div class="space-y-2">
                        @foreach ($newPhotos as $i => $file)
                            <div wire:key="new-photo-{{ $i }}" class="grid grid-cols-[64px_1fr_40px] gap-3 items-center p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                                <img src="{{ $file->temporaryUrl() }}" alt="" class="size-16 object-cover rounded" />
                                <flux:input
                                    wire:model="newPhotoCaptions.{{ $i }}"
                                    size="sm"
                                    placeholder="Caption (optional)"
                                />
                                <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click="removeNewPhoto({{ $i }})" />
                            </div>
                        @endforeach
                    </div>
                @endif
                <flux:error name="newPhotos" />
                <flux:error name="newPhotos.*" />
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
                    <flux:file-upload.dropzone>
                        <flux:icon.pencil-square class="size-6 text-zinc-400" />
                        <span class="text-sm font-medium">{{ $existingSig && ! $clearSignature ? 'Replace signature' : 'Upload customer signature' }}</span>
                        <flux:text size="xs" class="text-zinc-500">PNG / JPG · up to 2 MB</flux:text>
                    </flux:file-upload.dropzone>
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
