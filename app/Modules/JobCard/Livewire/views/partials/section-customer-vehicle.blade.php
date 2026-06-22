<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
    <div>
        <flux:heading size="lg">Customer & Vehicle</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Search by registration number or customer name — the customer is set automatically.</flux:text>
    </div>
    <div class="space-y-2 min-w-0">
        <flux:select
            wire:model.live="customer_vehicle_id"
            variant="listbox"
            searchable
            label="Customer & Vehicle"
            placeholder="Search reg no or customer…"
            required
        >
            @foreach ($this->vehiclePickerOptions as $v)
                <flux:select.option :value="$v['id']" wire:key="cv-{{ $v['id'] }}">{{ $v['label'] }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="customer_vehicle_id" />
        <flux:error name="customer_id" />
    </div>
</section>
