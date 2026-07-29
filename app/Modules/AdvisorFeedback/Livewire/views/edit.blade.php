@php($AF = \App\Modules\AdvisorFeedback\Models\AdvisorFeedback::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('advisor-feedback.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Advisor Feedback
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($feedback_no ?: 'Edit Feedback') : 'New Advisor Feedback' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">The advisor's rating of the customer after a job.</flux:text>
        </div>

        <flux:separator />

        {{-- CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Context</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Which customer / job.</flux:text>
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
                    <flux:input wire:model="invoice_reference" label="Invoice Ref" placeholder="Invoice no" class:input="font-mono" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Service Advisor" placeholder="Advisor…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ad-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="technician_id" variant="listbox" searchable clearable label="Technician" placeholder="Technician…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="tc-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="gate_pass_approval_id" variant="listbox" searchable clearable :filter="false" label="Gate Pass Ref" placeholder="GPA…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="gatePassSearch" placeholder="Search gate pass…" /></x-slot>
                        @foreach ($this->gatePasses as $gp)<flux:select.option :value="$gp->id" wire:key="gp-{{ $gp->id }}">{{ $gp->approval_no }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="service_type_id" variant="listbox" clearable label="Service Type" placeholder="Type…">
                        @foreach ($this->serviceTypes as $st)<flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- QUESTIONS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Rating</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Rate the customer 1–5 on each.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                @foreach ($AF::questions() as $field => $question)
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_160px] gap-2 items-center p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:text size="sm">{{ $question }}</flux:text>
                        <flux:select wire:model="{{ $field }}" variant="listbox" size="sm" clearable placeholder="Rate…">
                            @for ($n = 1; $n <= 5; $n++)<flux:select.option :value="$n">{{ $n }} ★</flux:select.option>@endfor
                        </flux:select>
                    </div>
                @endforeach

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                    <flux:select wire:model="status" variant="listbox" label="Feedback Status" required>
                        @foreach ($AF::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Any remarks about the customer." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('advisor-feedback.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Record feedback' }}</flux:button>
        </div>
    </form>
</div>
