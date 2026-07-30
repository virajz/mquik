@php($IWO = \App\Modules\InternalWorkOrder\Models\InternalWorkOrder::class)
@php($ATT = \App\Modules\InternalWorkOrder\Models\InternalWorkOrderAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('internal-work-order.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Internal Work Order
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($iwo_no ?: 'Edit IWO') : 'New Internal Work Order' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Raise an internal complaint / request / work order and track it to resolution.</flux:text>
        </div>

        <flux:separator />

        {{-- REQUEST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Request</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What, where and how urgent.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="iwo_type" variant="listbox" clearable label="IWO Type" placeholder="Complaint / Request…" autofocus>
                        @foreach ($IWO::types() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="iwo_category" variant="listbox" searchable clearable label="Category" placeholder="CCTV / Software / Electrical…">
                        @foreach ($IWO::categories() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="priority" variant="listbox" label="Priority" required>
                        @foreach ($IWO::priorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="title" label="Title" placeholder="Short summary of the issue / request" required />
                <flux:textarea wire:model="description" label="Description" rows="2" placeholder="Details — what's wrong, where, since when." />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="department" variant="listbox" clearable label="Department" placeholder="HR / IT / Stores…">
                        @foreach ($IWO::departments() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="requested_by_id" variant="listbox" searchable clearable label="Requested By" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="rb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="requested_to_id" variant="listbox" searchable clearable label="Requested To" placeholder="Owner / HOD…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="rt-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- RESPONSE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Response & Tracking</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Assignment, status and resolution.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="assigned_to_id" variant="listbox" searchable clearable label="Assigned To" placeholder="Handler…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="at-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model.live="status" variant="listbox" label="IWO Status" required>
                        @foreach ($IWO::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:date-picker wire:model="due_at" label="Due By" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="iwo_response" variant="listbox" searchable clearable label="IWO Response" placeholder="Accepted / Awaited…">
                        @foreach ($IWO::responses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode" variant="listbox" clearable label="Follow-up Mode" placeholder="WhatsApp / Call…">
                        @foreach ($IWO::followUpModes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-start">
                    <flux:select wire:model="root_cause" variant="listbox" clearable label="Root Cause" placeholder="If diagnosed…">
                        @foreach ($IWO::rootCauses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div>
                        <flux:select wire:model="corrective_action" variant="listbox" clearable label="Corrective Action" placeholder="Repair / Replacement…">
                            @foreach ($IWO::correctiveActions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="corrective_action" />
                    </div>
                    <flux:select wire:model="escalation" variant="listbox" clearable label="Escalation" placeholder="If escalated…">
                        @foreach ($IWO::escalations() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>

                {{-- Attachments --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Attachments</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                    </div>
                    @forelse ($attachments as $i => $att)
                        <div wire:key="iwo-att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end mb-2 p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Type…">
                                @foreach ($ATT::attachmentTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <div>
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" accept=".jpg,.jpeg,.png,.webp,.pdf,.mp4,.mov,.webm" />
                                @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Image / video / PDF / screenshot.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Handling remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('internal-work-order.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create IWO' }}</flux:button>
        </div>
    </form>
</div>
