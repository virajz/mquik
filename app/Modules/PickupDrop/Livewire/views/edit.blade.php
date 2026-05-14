<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('pickup-drop.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Pickup / Drop
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? 'Pickup/Drop '.$pickup_drop_no : 'New Pickup / Drop' }}
                </flux:heading>
                @if ($appointment_id)
                    <flux:text size="sm" class="mt-1 text-zinc-500">Linked to Appointment #{{ $appointment_id }}</flux:text>
                @endif
            </div>
            @if ($editingId)
                <flux:badge :color="match ($status) {
                    'scheduled' => 'amber', 'picked' => 'blue', 'in_transit' => 'sky',
                    'delivered' => 'lime', 'cancelled' => 'zinc', default => 'zinc',
                }" size="lg">{{ \App\Modules\PickupDrop\Models\PickupDrop::statuses()[$status] }}</flux:badge>
            @endif
        </div>

        <flux:separator />

        {{-- DIRECTION & SCHEDULE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Direction & Schedule</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Pickup brings the vehicle in; Drop takes it back. When and current state.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="direction" variant="listbox" label="Direction" required>
                        @foreach (\App\Modules\PickupDrop\Models\PickupDrop::directions() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:date-picker wire:model="scheduled_date" label="Date" placeholder="Select date" with-today selectable-header fixed-weeks type="input" />
                    <flux:time-picker wire:model="scheduled_time" label="Time" placeholder="Select time" type="input" />
                </div>

                <flux:select wire:model="status" variant="listbox" label="Status" class="md:max-w-xs" required>
                    @foreach (\App\Modules\PickupDrop\Models\PickupDrop::statuses() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- CUSTOMER & VEHICLE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Customer & Vehicle</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Customer first; the vehicle picker filters to their vehicles.</flux:text>
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

        {{-- LOCATION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Location & Contact</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Where to pick up from / drop to, and who the driver should call.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                @if ($this->customerAddresses->isNotEmpty())
                    <flux:select wire:model.live="address_choice" variant="listbox" label="Saved address">
                        @foreach ($this->customerAddresses as $addr)
                            <flux:select.option :value="(string) $addr['id']" wire:key="addr-{{ $addr['id'] }}">
                                {{ $addr['label'] ?? 'Address' }}{{ $addr['is_primary'] ? ' (Primary)' : '' }} — {{ \Illuminate\Support\Str::limit($addr['full'], 60) }}
                            </flux:select.option>
                        @endforeach
                        <flux:select.option value="custom">Other / type a new address…</flux:select.option>
                    </flux:select>
                @endif

                <flux:textarea wire:model="address" label="Address" placeholder="House / street / area / pincode" rows="2" required />

                <flux:field>
                    <flux:label>Contact Phone</flux:label>
                    <flux:input.group>
                        <flux:input.group.prefix>+91</flux:input.group.prefix>
                        <flux:input wire:model="contact_phone" mask="99999 99999" placeholder="98765 43210" inputmode="numeric" />
                    </flux:input.group>
                    <flux:error name="contact_phone" />
                </flux:field>
            </div>
        </section>

        <flux:separator />

        {{-- ASSIGNMENT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Assignment</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Assign EITHER an in-house driver OR an outside courier vendor — not both.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="driver_employee_id" variant="listbox" searchable clearable label="In-house Driver" :placeholder="$vendor_courier_id ? 'Vendor selected — clear it first' : 'Pick a driver…'" :disabled="(bool) $vendor_courier_id">
                        @foreach ($this->drivers as $d)
                            <flux:select.option :value="$d->id" wire:key="drv-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="vendor_courier_id" variant="listbox" searchable clearable label="Vendor Courier" :placeholder="$driver_employee_id ? 'Driver selected — clear it first' : 'Pick a vendor…'" :disabled="(bool) $driver_employee_id">
                        @foreach ($this->couriers as $c)
                            <flux:select.option :value="$c->id" wire:key="cou-{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:error name="driver_employee_id" />
                <flux:error name="vendor_courier_id" />
            </div>
        </section>

        <flux:separator />

        {{-- NOTES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Notes</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Anything the driver should know — gate code, parking, customer language.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea wire:model="notes" label="Driver Notes" placeholder="Apartment 3B; ask for Suresh; gate closes at 8 PM." rows="3" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('pickup-drop.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Schedule Pickup/Drop' }}</flux:button>
        </div>
    </form>
</div>
