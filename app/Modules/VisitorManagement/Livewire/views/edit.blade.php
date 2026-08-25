@php($VMS = \App\Modules\VisitorManagement\Models\VisitorVisit::class)
@php($ATT = \App\Modules\VisitorManagement\Models\VisitorVisitAttachment::class)
<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('visitor-management.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Visitor Management (VMS)
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($token_no ?: 'Edit Visit') : 'New Visit' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Issue a token and track the visit through consultation.</flux:text>
        </div>

        <flux:separator />

        {{-- VISITOR --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Visitor & Vehicle</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who arrived and why.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Customer…" autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Search customer…" /></x-slot>
                        @foreach ($this->customers as $c)<flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ trim($c->first_name.' '.$c->last_name) }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                        @foreach ($this->vehicles as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_type" variant="listbox" clearable label="Customer Type" placeholder="Senior / Lady / Gents…">
                        @foreach ($VMS::customerTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="visit_purpose" variant="listbox" clearable label="Visit Purpose" placeholder="Purpose…">
                        @foreach ($VMS::visitPurposes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="arrival_mode" variant="listbox" clearable label="Arrival Mode" placeholder="Walk in / Appointment…">
                        @foreach ($VMS::arrivalModes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="department_id" variant="listbox" searchable clearable label="Department" placeholder="Department…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dep-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ADVISOR --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Advisor Assignment</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Reception and advisor allocation.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="assigned_by_id" variant="listbox" searchable clearable label="Assigned By (Reception)" placeholder="Reception executive…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ab-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="assigned_to_id" variant="listbox" searchable clearable label="Assigned To (Advisor)" placeholder="Service advisor / CRM…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="at-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="advisor_assignment_method" variant="listbox" clearable label="Assignment Method" placeholder="Auto / Manual…">
                        @foreach ($VMS::advisorAssignmentMethods() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="advisor_availability" variant="listbox" clearable label="Advisor Availability" placeholder="Available / Busy…">
                        @foreach ($VMS::advisorAvailabilities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="waiting_time_category" variant="listbox" clearable label="Waiting Time" placeholder="Bucket…">
                        @foreach ($VMS::waitingTimeCategories() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- QUEUE STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Queue Status</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Waiting lifecycle, delays and no-shows.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Waiting Status" required>
                        @foreach ($VMS::waitingStatuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="delay_reason" variant="listbox" clearable label="Delay Reason" placeholder="If delayed…">
                        @foreach ($VMS::delayReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.status === 'cancelled'" x-cloak>
                    <flux:select wire:model="no_show_reason" variant="listbox" clearable label="No-Show Reason" placeholder="Why cancelled…" class="md:max-w-sm">
                        @foreach ($VMS::noShowReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="no_show_reason" />
                </div>
                <flux:input wire:model="announcement_message" label="Announcement Message" placeholder="Queue display announcement" />

                {{-- Attachments --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Attachments</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                    </div>
                    @forelse ($attachments as $i => $att)
                        <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end mb-2 p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Type…">
                                @foreach ($ATT::attachmentTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <div>
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                                @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Token slip / customer note / visit note.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Visit remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('visitor-management.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create visit' }}</flux:button>
        </div>
    </form>
</div>
