<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
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
