<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Appointments</flux:heading>
            <flux:text class="mt-1">Customer service bookings — when, who, which vehicle, which advisor.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('appointment.create')
                <flux:button variant="primary" icon="plus" :href="route('appointment.create')" wire:navigate>New Appointment</flux:button>
            @endcan
        </div>
    </div>

    {{-- Search and status stay on the surface because they are what people reach
         for; the rest live behind one button, with chips showing what is applied
         so the narrowed state is never invisible. --}}
    <div class="mb-3 flex items-center gap-3">
        {{-- Flux puts `class` on the inner control, not the wrapper, so the flex
             sizing has to live on a wrapping div or the search collapses and the
             dropdown eats the row. --}}
        <div class="flex-1 min-w-0">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Appointment no, job card no, customer, phone, reg no, brand/model…"
                icon="magnifying-glass"
                clearable
                class="w-full"
            />
        </div>

        <div class="w-44 shrink-0">
            <flux:select wire:model.live="statusFilter" variant="listbox" class="w-full">
                <flux:select.option value="open">Open (unfinished)</flux:select.option>
                <flux:select.option value="all">All status</flux:select.option>
                @foreach ($statuses as $key => $label)
                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @php($activeFilters = $this->activeFilters())
        <flux:dropdown class="shrink-0">
            <flux:button icon="funnel" variant="{{ count($activeFilters) ? 'primary' : 'outline' }}">
                Filters
                @if (count($activeFilters))
                    <flux:badge size="sm" color="zinc" class="ms-1.5">{{ count($activeFilters) }}</flux:badge>
                @endif
            </flux:button>

            <flux:popover class="w-80 space-y-4">
                <flux:select wire:model.live="channelFilter" variant="listbox" label="Booking Channel">
                    <flux:select.option value="all">All channels</flux:select.option>
                    @foreach ($channels as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="advisorFilter" variant="listbox" searchable label="Advisor">
                    <flux:select.option value="all">All advisors</flux:select.option>
                    @foreach ($this->advisors as $a)
                        <flux:select.option :value="(string) $a->id">{{ $a->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="deptFilter" variant="listbox" searchable label="Department">
                    <flux:select.option value="all">All departments</flux:select.option>
                    @foreach ($this->departments as $d)
                        <flux:select.option :value="(string) $d->id">{{ $d->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="slotFilter" variant="listbox" searchable label="Time Slot">
                    <flux:select.option value="all">All time slots</flux:select.option>
                    @foreach ($this->timeSlots as $slot)
                        <flux:select.option :value="(string) $slot->id" wire:key="fslot-{{ $slot->id }}">{{ $slot->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="pickupDropFilter" variant="listbox" searchable label="Pickup / Drop">
                    <flux:select.option value="all">All pickup/drop</flux:select.option>
                    @foreach ($this->pickupDropOptions as $opt)
                        <flux:select.option :value="(string) $opt->id" wire:key="fpd-{{ $opt->id }}">{{ $opt->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:separator variant="subtle" />

                {{-- One range, pointed at whichever date is being asked about. --}}
                <flux:select wire:model.live="dateField" variant="listbox" label="Date range applies to">
                    <flux:select.option value="appointment_at">Appointment date</flux:select.option>
                    <flux:select.option value="created_at">Created date</flux:select.option>
                </flux:select>

                <div class="grid grid-cols-2 gap-2">
                    <flux:date-picker locale="en-IN" wire:model.live="dateFrom" placeholder="From" with-today selectable-header fixed-weeks type="input" clearable />
                    <flux:date-picker locale="en-IN" wire:model.live="dateTo" placeholder="To" with-today selectable-header fixed-weeks type="input" clearable />
                </div>
            </flux:popover>
        </flux:dropdown>

        {{-- Which columns to show is a personal habit, so it sits beside the
             filters and is remembered in the session, not the URL. --}}
        <flux:dropdown class="shrink-0">
            <flux:button icon="view-columns" variant="outline">
                Columns
                <flux:badge size="sm" color="zinc" class="ms-1.5">{{ count($visibleColumns) }}/{{ count($optionalColumns) }}</flux:badge>
            </flux:button>

            <flux:popover class="w-56 flex flex-col gap-3">
                <flux:checkbox.group wire:model.live="visibleColumns" class="flex flex-col gap-2">
                    @foreach ($optionalColumns as $key => $label)
                        <flux:checkbox :value="$key" :label="$label" wire:key="col-{{ $key }}" />
                    @endforeach
                </flux:checkbox.group>

                <flux:separator variant="subtle" />

                <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="resetColumns">Show all</flux:button>
            </flux:popover>
        </flux:dropdown>
    </div>

    @if ($search || count($activeFilters))
        <div class="mb-4 flex items-center gap-2 flex-wrap">
            @foreach ($activeFilters as $key => $chip)
                <flux:badge size="sm" variant="pill" wire:key="chip-{{ $key }}">
                    <span class="text-zinc-500">{{ $chip['label'] }}:</span>&nbsp;{{ $chip['value'] }}
                    <flux:badge.close wire:click="removeFilter('{{ $key }}')" />
                </flux:badge>
            @endforeach

            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear all</flux:button>
        </div>
    @endif

    {{-- One line per booking. The two-line cells this replaced doubled every
         row height for data that reads fine inline. --}}
    <flux:table>
        <flux:table.columns>
            {{-- Frozen: with this many columns the row identity and its actions
                 must stay reachable however far the table is scrolled. Flux's
                 own `sticky` prop pins the first and last columns; it supplies
                 the positioning and scroll shadow but no background, so the
                 page surface colour goes on with it. --}}
            <flux:table.column sticky class="w-24 bg-white dark:bg-zinc-900" sortable :sorted="$sortBy === 'appointment_no'" :direction="$sortDirection" wire:click="sort('appointment_no')">No.</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'appointment_at'" :direction="$sortDirection" wire:click="sort('appointment_at')">When</flux:table.column>
            @if ($this->showsColumn('entry'))
                <flux:table.column class="w-32" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Entry</flux:table.column>
            @endif
            @if ($this->showsColumn('job_card'))
                <flux:table.column class="w-28">Job Card</flux:table.column>
            @endif
            <flux:table.column>Vehicle / Customer</flux:table.column>
            @if ($this->showsColumn('advisor'))
                <flux:table.column class="w-40">Advisor / Dept</flux:table.column>
            @endif
            @if ($this->showsColumn('channel'))
                <flux:table.column class="w-28">Channel</flux:table.column>
            @endif
            @if ($this->showsColumn('pickup_drop'))
                <flux:table.column class="w-36">Pickup / Drop</flux:table.column>
            @endif
            @if ($this->showsColumn('slots'))
                <flux:table.column class="w-40">Time Slots</flux:table.column>
            @endif
            @if ($this->showsColumn('priority'))
                <flux:table.column class="w-20">Priority</flux:table.column>
            @endif
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            @if ($this->showsColumn('pending_reason'))
                <flux:table.column class="w-32">Pending Reason</flux:table.column>
            @endif
            <flux:table.column sticky class="w-24 bg-white dark:bg-zinc-900" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell sticky class="font-mono text-xs bg-white dark:bg-zinc-900">
                        <flux:link :href="route('appointment.edit', $row)" wire:navigate variant="ghost">{{ $row->appointment_no ?? '—' }}</flux:link>
                    </flux:table.cell>

                    {{-- Always the appointment itself. This used to prefer the pickup
                         slot, so a collected vehicle showed its collection window
                         under a column headed "When" — the wrong time entirely. The
                         two legs live in the pickup/drop tooltip instead. --}}
                    <flux:table.cell class="text-xs">
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $row->appointment_at?->format('d/m/Y') }}</span>
                        <span class="ms-1 text-zinc-500">{{ $row->appointment_at?->format('h:i A') }}</span>
                    </flux:table.cell>

                    @if ($this->showsColumn('entry'))
                        {{-- When the booking was taken, as distinct from when the car is due in. --}}
                        <flux:table.cell class="text-xs text-zinc-500">
                            {{ $row->created_at?->format('d/m/Y') }}<span class="ms-1">{{ $row->created_at?->format('h:i A') }}</span>
                        </flux:table.cell>
                    @endif

                    @if ($this->showsColumn('job_card'))
                        {{-- The job card this booking became, if it got that far. --}}
                        <flux:table.cell class="font-mono text-xs">
                            @php($jobCard = $row->jobCards->first())
                            @if ($jobCard)
                                <flux:link :href="route('job-card.edit', $jobCard)" wire:navigate variant="ghost">{{ $jobCard->job_card_no }}</flux:link>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                    @endif

                    {{-- Registration first: it is what the workshop actually calls
                         a booking by. Brand is dropped — the model alone identifies
                         the car and the brand only ate width. --}}
                    <flux:table.cell class="text-xs">
                        <div class="truncate max-w-[26rem]">
                            @if ($row->customerVehicle)
                                <span class="font-medium font-mono text-zinc-800 dark:text-white">{{ $row->customerVehicle->registration_no }}</span>
                                <span class="text-zinc-500">· {{ $row->customerVehicle->model?->name ?? '—' }}</span>
                            @endif
                            <span class="text-zinc-500">· {{ trim($row->customer?->first_name.' '.($row->customer?->last_name ?? '')) }}</span>
                            @if ($row->customer?->phone)
                                <span class="text-zinc-500 font-mono">· {{ $row->customer->phone }}</span>
                            @endif
                        </div>
                    </flux:table.cell>

                    @if ($this->showsColumn('advisor'))
                        <flux:table.cell class="text-xs">
                            <div class="truncate max-w-40" title="{{ trim(($row->advisor?->name ?? '—').' · '.($row->workshopDepartment?->name ?? '—')) }}">
                                {{ $row->advisor?->name ?? '—' }}
                                <span class="text-zinc-500">· {{ $row->workshopDepartment?->name ?? '—' }}</span>
                            </div>
                        </flux:table.cell>
                    @endif

                    @if ($this->showsColumn('channel'))
                        <flux:table.cell class="text-xs text-zinc-500">
                            <div class="truncate max-w-28">{{ $row->bookingChannel?->name ?? '—' }}</div>
                        </flux:table.cell>
                    @endif

                    @if ($this->showsColumn('pickup_drop'))
                        {{-- Its own column now. It used to be a truck icon hidden inside
                             Channel, which said a vehicle moves but never which way. --}}
                        <flux:table.cell class="text-xs">
                            @php($leg = $row->pickupDrops->first())
                            @if ($row->pickupDropOption || $leg)
                                {{-- The job when one exists, otherwise the option
                                     the customer chose. The option is a plan; the
                                     job is the thing you can actually open. --}}
                                <div class="truncate max-w-36" title="{{ trim(($row->pickupDropOption?->name ?? '').($leg ? ' — '.$leg->pickup_drop_no : '')) }}">
                                    @if ($row->pickupDropOption?->involves_pickup || $row->pickupDropOption?->involves_drop)
                                        <flux:icon.truck class="inline size-3.5 text-amber-600 align-text-bottom" />
                                    @endif
                                    @if ($leg)
                                        <flux:link :href="route('pickup-drop.edit', $leg)" wire:navigate variant="ghost" class="font-mono">{{ $leg->pickup_drop_no }}</flux:link>
                                    @else
                                        <span class="text-zinc-500">{{ $row->pickupDropOption->name }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                    @endif

                    @if ($this->showsColumn('slots'))
                        {{-- Start times inline so the row stays one line; the full
                             windows are on hover, since that is the detail you only
                             want when you are actually looking at one booking. --}}
                        <flux:table.cell class="text-xs text-zinc-500">
                            @if ($row->timeSlot || $row->dropTimeSlot)
                                <div class="truncate max-w-40" title="{{ collect([
                                    $row->timeSlot ? 'Pickup '.$row->timeSlot->window() : null,
                                    $row->dropTimeSlot ? 'Drop '.$row->dropTimeSlot->window() : null,
                                ])->filter()->implode(' · ') }}">
                                    @if ($row->timeSlot)
                                        <span class="text-zinc-400">P</span> {{ $row->timeSlot->window() }}
                                    @endif
                                    @if ($row->timeSlot && $row->dropTimeSlot)
                                        <span class="text-zinc-300">·</span>
                                    @endif
                                    @if ($row->dropTimeSlot)
                                        <span class="text-zinc-400">D</span> {{ $row->dropTimeSlot->window() }}
                                    @endif
                                </div>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                    @endif

                    @if ($this->showsColumn('priority'))
                        <flux:table.cell class="text-xs text-zinc-500">
                            @if ($row->priority)
                                @php($priorityColor = match (strtoupper($row->priority->name)) {
                                    'URGENT' => 'red', 'HIGH' => 'amber', default => 'zinc',
                                })
                                <flux:badge :color="$priorityColor" size="sm">{{ $row->priority->name }}</flux:badge>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                    @endif

                    <flux:table.cell>
                        @php($statusColor = match ($row->status) {
                            'pending' => 'amber', 'confirmed' => 'blue', 'rescheduled' => 'purple',
                            'vehicle_collected' => 'indigo', 'arrived' => 'purple',
                            'completed' => 'lime', 'cancelled' => 'zinc', 'no_show' => 'red', default => 'zinc',
                        })
                        <flux:badge :color="$statusColor" size="sm">{{ $row->effectiveStatusLabel() }}</flux:badge>
                    </flux:table.cell>

                    @if ($this->showsColumn('pending_reason'))
                        {{-- Only meaningful while a booking is pending — why it is stuck
                             is the thing a coordinator scans this list for. --}}
                        <flux:table.cell class="text-xs text-zinc-500">
                            <div class="truncate max-w-32" title="{{ $row->status === \App\Modules\Appointment\Models\Appointment::STATUS_PENDING ? ($row->pendingReason?->name ?? 'Not specified') : '' }}">
                                @if ($row->status === \App\Modules\Appointment\Models\Appointment::STATUS_PENDING)
                                    {{ $row->pendingReason?->name ?? 'Not specified' }}
                                @else
                                    —
                                @endif
                            </div>
                        </flux:table.cell>
                    @endif

                    <flux:table.cell sticky class="bg-white dark:bg-zinc-900">
                        <div class="flex items-center justify-end gap-0.5">
                            @can('job_card.create')
                                <flux:tooltip content="Create job card from this appointment">
                                    <flux:button size="xs" variant="ghost" icon="clipboard-document-check" :href="route('job-card.create', ['from-appointment' => $row->id])" wire:navigate />
                                </flux:tooltip>
                            @endcan
                            @can('pickup_drop.create')
                                <flux:tooltip content="Create pickup / drop from this appointment">
                                    <flux:button size="xs" variant="ghost" icon="truck" :href="route('pickup-drop.create', ['from-appointment' => $row->id])" wire:navigate />
                                </flux:tooltip>
                            @endcan
                            @can('appointment.update')
                                <flux:tooltip content="Edit">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" :href="route('appointment.edit', $row)" wire:navigate />
                                </flux:tooltip>
                            @endcan
                            @can('appointment.delete')
                                <flux:tooltip content="Delete">
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete({{ $row->id }})" />
                                </flux:tooltip>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell :colspan="$this->columnCount" class="text-center text-zinc-500 py-12">
                        <flux:icon.calendar-days class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No appointments yet</div>
                        <flux:text class="mt-1">Booking from app, website, email, or phone — they all land here.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Shared by every row; the row id lives on the component. --}}
    @can('appointment.delete')
        <flux:modal name="appointment-delete" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">Delete {{ $this->deleting?->appointment_no ?? 'this appointment' }}?</flux:heading>
                <flux:text>Cannot be undone. If this appointment has spawned a job card, the delete will fail.</flux:text>
                <div class="flex gap-2 justify-end">
                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                    <flux:button variant="danger" wire:click="delete">Delete</flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
