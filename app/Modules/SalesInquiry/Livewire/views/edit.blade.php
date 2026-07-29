@php($SI = \App\Modules\SalesInquiry\Models\SalesInquiry::class)
@php($ATT = \App\Modules\SalesInquiry\Models\SalesInquiryAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('sales-inquiry.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Sales Inquiries
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($inquiry_no ?: 'Edit Inquiry') : 'New Sales Inquiry' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Capture a customer service / parts inquiry.</flux:text>
        </div>

        <flux:separator />

        {{-- INQUIRY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Inquiry</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who, what and from where.</flux:text>
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
                    <flux:select wire:model="priority" variant="listbox" label="Priority" required>
                        @foreach ($SI::priorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="inquiry_type" variant="listbox" searchable clearable label="Inquiry Type" placeholder="What they want…">
                        @foreach ($SI::inquiryTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="inquiry_source" variant="listbox" clearable label="Inquiry Source" placeholder="Where from…">
                        @foreach ($SI::inquirySources() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input.group label="Estimated Value">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="estimated_value" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
                <flux:textarea wire:model="inquiry_details" label="Inquiry Details" rows="2" placeholder="What the customer is asking for (parts / labour / service)." />
            </div>
        </section>

        <flux:separator />

        {{-- ASSIGNMENT & FOLLOW-UP --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Assignment & Follow-up</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who owns it, and where it stands.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="assigned_by_id" variant="listbox" searchable clearable label="Assigned By" placeholder="Reception / CRM…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ab-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="assigned_to_id" variant="listbox" searchable clearable label="Assigned To" placeholder="Advisor / Store…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="at-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Inquiry Status" required>
                        @foreach ($SI::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_attempt" variant="listbox" clearable label="Follow-up Attempt" placeholder="1st / 2nd…">
                        @foreach ($SI::followUpAttempts() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.status === 'lost_opportunity'" x-cloak>
                    <flux:select wire:model="lost_reason" variant="listbox" clearable label="Lost Reason" placeholder="Why lost…" class="md:max-w-sm">
                        @foreach ($SI::lostReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="lost_reason" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" x-data>
                    <flux:select wire:model.live="escalation" variant="listbox" clearable label="Escalation" placeholder="If escalated…">
                        @foreach ($SI::escalations() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.escalation" x-cloak>
                        <flux:select wire:model="escalation_reason" variant="listbox" clearable label="Escalation Reason" placeholder="Why escalated…">
                            @foreach ($SI::escalationReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="escalation_reason" />
                    </div>
                </div>

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
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" accept=".jpg,.jpeg,.png,.webp,.pdf,.mp3,.m4a,.ogg" />
                                @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">VIN photo / vehicle photos / voice recording.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Follow-up remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('sales-inquiry.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Register inquiry' }}</flux:button>
        </div>
    </form>
</div>
