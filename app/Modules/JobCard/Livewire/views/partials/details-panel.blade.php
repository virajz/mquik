{{-- Read-only customer + vehicle details — rendered in the sticky side panel. --}}
@if ($this->selectedCustomer || $this->selectedVehicle)
    @php($loc = $this->customerLocation())
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/30 divide-y divide-zinc-200 dark:divide-zinc-800">
        @if ($this->selectedCustomer)
            @php($cust = $this->selectedCustomer)
            <div class="p-4 space-y-3">
                <div class="flex items-center gap-2">
                    <flux:icon.user-circle variant="micro" class="size-4 text-zinc-400" />
                    <flux:text size="xs" class="font-semibold uppercase tracking-wide text-zinc-400">Customer</flux:text>
                </div>
                <div class="text-sm font-medium">{{ trim($cust->first_name.' '.($cust->last_name ?? '')) }}</div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5">
                    @foreach ([
                        'Customer Type' => $cust->businessType?->name,
                        'GST Type' => $cust->gstType?->name,
                        'State' => $loc['state'],
                        'City' => $loc['city'],
                        'Area' => $loc['area'],
                        'Zip Code' => $loc['pincode'],
                    ] as $label => $value)
                        @if ($value)
                            <div class="min-w-0">
                                <dt class="text-[11px] uppercase tracking-wide text-zinc-400">{{ $label }}</dt>
                                <dd class="text-sm font-medium truncate">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        @endif

        @if ($this->selectedVehicle)
            @php($veh = $this->selectedVehicle)
            <div class="p-4 space-y-3">
                <div class="flex items-center gap-2">
                    <flux:icon.truck variant="micro" class="size-4 text-zinc-400" />
                    <flux:text size="xs" class="font-semibold uppercase tracking-wide text-zinc-400">Vehicle</flux:text>
                </div>
                <div class="text-sm font-medium">{{ trim(($veh->model?->brand?->name ?? '').' '.($veh->model?->name ?? '')) }}</div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5">
                    @foreach ([
                        'Variant' => $veh->variant?->name,
                        'Fuel Type' => $veh->variant?->fuelType?->name,
                        'Transmission' => $veh->variant?->transmissionType?->name,
                        'Type / Segment' => $veh->model?->vehicleSegment?->name,
                        'Colour' => $veh->color?->name,
                        'Registration Type' => $veh->registrationType?->name,
                    ] as $label => $value)
                        @if ($value)
                            <div class="min-w-0">
                                <dt class="text-[11px] uppercase tracking-wide text-zinc-400">{{ $label }}</dt>
                                <dd class="text-sm font-medium truncate">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                    @if ($veh->vin)
                        <div class="col-span-2 min-w-0">
                            <dt class="text-[11px] uppercase tracking-wide text-zinc-400">VIN</dt>
                            <dd class="text-xs font-mono font-medium truncate">{{ $veh->vin }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif
    </div>
@else
    <div class="flex h-40 items-center justify-center rounded-lg border border-dashed border-zinc-200 dark:border-zinc-800 p-4 text-center text-sm text-zinc-400">
        Pick a vehicle to see customer &amp; vehicle details here.
    </div>
@endif
