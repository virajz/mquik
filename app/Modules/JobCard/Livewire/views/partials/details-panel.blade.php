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

                {{-- Contact first: it is what an advisor reaches for when the car
                     is ready or a decision is needed. Tap-to-call on mobile. --}}
                @if ($cust->phone || $cust->alternate_phone || $cust->email)
                    <div class="space-y-1.5">
                        @if ($cust->phone)
                            <div class="flex items-center gap-2">
                                <flux:icon.phone variant="micro" class="size-3.5 shrink-0 text-zinc-400" />
                                <a href="tel:{{ $cust->phone }}" class="text-sm font-medium hover:underline">+91 {{ $cust->phone }}</a>
                            </div>
                        @endif
                        @if ($cust->alternate_phone)
                            <div class="flex items-center gap-2">
                                <flux:icon.phone variant="micro" class="size-3.5 shrink-0 text-zinc-400" />
                                <a href="tel:{{ $cust->alternate_phone }}" class="text-sm text-zinc-500 hover:underline">+91 {{ $cust->alternate_phone }}</a>
                                <span class="text-[11px] uppercase tracking-wide text-zinc-400">alt</span>
                            </div>
                        @endif
                        @if ($cust->email)
                            <div class="flex items-center gap-2 min-w-0">
                                <flux:icon.envelope variant="micro" class="size-3.5 shrink-0 text-zinc-400" />
                                <a href="mailto:{{ $cust->email }}" class="truncate text-sm text-zinc-500 hover:underline">{{ $cust->email }}</a>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- The whole address in one block rather than four scattered cells —
                     it is read as an address, not as separate fields. --}}
                @php($address = collect([
                    $cust->primaryAddress?->address_line,
                    $loc['area'],
                    $loc['city'],
                    $loc['state'],
                    $loc['pincode'],
                ])->filter()->implode(', '))
                @if ($address !== '')
                    <div class="flex items-start gap-2">
                        <flux:icon.map-pin variant="micro" class="mt-0.5 size-3.5 shrink-0 text-zinc-400" />
                        <div class="min-w-0">
                            <dt class="text-[11px] uppercase tracking-wide text-zinc-400">Address</dt>
                            <dd class="text-sm">{{ $address }}</dd>
                        </div>
                    </div>
                @endif

                <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5">
                    @foreach ([
                        'Customer Type' => $cust->businessType?->name,
                        'GST Type' => $cust->gstType?->name,
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
                        'Segment' => $veh->model?->vehicleSegment?->name,
                        'Variant' => $veh->variant?->name,
                        'Fuel Type' => $veh->variant?->fuelType?->name,
                        'Transmission' => $veh->variant?->transmissionType?->name,
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
