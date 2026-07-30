@php($SMA = \App\Modules\StockMismatchApproval\Models\StockMismatchApproval::class)
@php($ATT = \App\Modules\StockMismatchApproval\Models\StockMismatchApprovalAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('stock-mismatch-approval.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Stock Mismatch Approval
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($approval_no ?: 'Edit Approval') : 'New Stock Mismatch Approval' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Raise and track a stock-count variance approval.</flux:text>
        </div>

        <flux:separator />

        {{-- REQUEST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Request</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">The stock count, requester and approver.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="stock_count_id" variant="listbox" searchable clearable :filter="false" label="Stock Count" placeholder="SC-…" autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="countSearch" placeholder="Search count…" /></x-slot>
                        @foreach ($this->stockCounts as $c)<flux:select.option :value="$c->id" wire:key="sc-{{ $c->id }}">{{ $c->count_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="requested_by_id" variant="listbox" searchable clearable label="Requested By" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="rb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="requested_to_id" variant="listbox" searchable clearable label="Requested To" placeholder="Approver…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="rt-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="variance_reason" variant="listbox" searchable clearable label="Variance Reason" placeholder="Why the mismatch…">
                        @foreach ($SMA::varianceReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="communication_mode" variant="listbox" clearable label="Communication Mode" placeholder="How communicated…">
                        @foreach ($SMA::communicationModes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- RESPONSE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Response</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Management decision and outcome.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-start">
                    <flux:select wire:model.live="approval_status" variant="listbox" label="Approval Status" required>
                        @foreach ($SMA::approvalStatuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div>
                        <flux:select wire:model="management_response" variant="listbox" clearable label="Management Response" placeholder="Adjust / Recount…">
                            @foreach ($SMA::managementResponses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="management_response" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="recount_outcome" variant="listbox" clearable label="Recount Outcome" placeholder="If recounted…">
                        @foreach ($SMA::recountOutcomes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="adjustment_method" variant="listbox" clearable label="Adjustment Method" placeholder="FOC / Issue…">
                        @foreach ($SMA::adjustmentMethods() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
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
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                                @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Mismatch note / investigation note / approval note.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Approval remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('stock-mismatch-approval.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create approval' }}</flux:button>
        </div>
    </form>
</div>
