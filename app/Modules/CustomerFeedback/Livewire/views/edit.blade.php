@php($CF = \App\Modules\CustomerFeedback\Models\CustomerFeedback::class)
@php($ATT = \App\Modules\CustomerFeedback\Models\CustomerFeedbackAttachment::class)
<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('customer-feedback.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Customer Feedback
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($feedback_no ?: 'Edit Feedback') : 'New Customer Feedback' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Capture post-service ratings and follow-up.</flux:text>
        </div>

        <flux:separator />

        {{-- FOLLOW-UP --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Follow-up</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">When and how the customer was contacted.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <flux:select wire:model.live="follow_up_schedule" variant="listbox" clearable label="Follow-up Schedule" placeholder="4 / 7 / 15 / 30 days…" autofocus>
                        @foreach ($CF::followUpSchedules() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.follow_up_schedule === 'custom'" x-cloak>
                        <flux:input wire:model="follow_up_custom_days" type="number" min="1" max="365" label="Custom (days)" class:input="font-mono" />
                        <flux:error name="follow_up_custom_days" />
                    </div>
                    <flux:select wire:model="follow_up_category" variant="listbox" clearable label="Category" placeholder="Quality / Performance…">
                        @foreach ($CF::followUpCategories() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="follow_up_mode" variant="listbox" clearable label="Follow-up Mode" placeholder="WhatsApp / Call…">
                        @foreach ($CF::followUpModes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_attempt" variant="listbox" clearable label="Attempt" placeholder="1st / 2nd…">
                        @foreach ($CF::followUpAttempts() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="vehicle_observation" variant="listbox" clearable label="Vehicle Observation" placeholder="Running normally…">
                        @foreach ($CF::vehicleObservations() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Context</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Customer, vehicle and who served them.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Customer…">
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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="service_type_id" variant="listbox" clearable label="Service Type" placeholder="Type…">
                        @foreach ($this->serviceTypes as $st)<flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="feedback_source" variant="listbox" clearable label="Feedback Source" placeholder="WhatsApp / Google…">
                        @foreach ($CF::feedbackSources() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- RATINGS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Ratings</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each question is 1–5 stars.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="staff_experience_rating" variant="listbox" clearable label="Staff Experience">
                        @for ($n = 1; $n <= 5; $n++)<flux:select.option :value="$n">{{ $n }} ★</flux:select.option>@endfor
                    </flux:select>
                    <flux:select wire:model="service_experience_rating" variant="listbox" clearable label="Service Experience">
                        @for ($n = 1; $n <= 5; $n++)<flux:select.option :value="$n">{{ $n }} ★</flux:select.option>@endfor
                    </flux:select>
                    <flux:select wire:model="service_rating" variant="listbox" clearable label="Service Rating">
                        @for ($n = 1; $n <= 5; $n++)<flux:select.option :value="$n">{{ $n }} ★</flux:select.option>@endfor
                    </flux:select>
                    <flux:select wire:model="price_rating" variant="listbox" clearable label="Service Price">
                        @for ($n = 1; $n <= 5; $n++)<flux:select.option :value="$n">{{ $n }} ★</flux:select.option>@endfor
                    </flux:select>
                    <flux:select wire:model="ontime_delivery_rating" variant="listbox" clearable label="On-time Delivery">
                        @for ($n = 1; $n <= 5; $n++)<flux:select.option :value="$n">{{ $n }} ★</flux:select.option>@endfor
                    </flux:select>
                    <flux:field variant="inline" class="pt-6">
                        <flux:checkbox wire:model="would_recommend" />
                        <flux:label>Would recommend Mquik</flux:label>
                    </flux:field>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="feedback_category" variant="listbox" clearable label="Feedback Category" placeholder="Complaint / Praise…">
                        @foreach ($CF::feedbackCategories() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="status" variant="listbox" label="Feedback Status" required>
                        @foreach ($CF::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>

                {{-- Attachment --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Feedback Screenshot</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                    </div>
                    @forelse ($attachments as $i => $att)
                        <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end mb-2 p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <div>
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" label="File" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                                @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Attach the feedback screenshot.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Any comments the customer left." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('customer-feedback.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Record feedback' }}</flux:button>
        </div>
    </form>
</div>
