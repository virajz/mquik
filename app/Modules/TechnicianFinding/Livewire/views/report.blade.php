@php($TF = \App\Modules\TechnicianFinding\Models\TechnicianFinding::class)
<div class="max-w-4xl">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <flux:link :href="route('job-card.edit', $jobCard->id)" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> {{ $jobCard->job_card_no }}
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">Findings Report</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">
                What the technicians found on
                {{ $jobCard->customerVehicle?->registration_no ?: 'this vehicle' }}
                @if ($jobCard->advisor)· Advisor: {{ $jobCard->advisor->name }}@endif
            </flux:text>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <flux:switch wire:model.live="openOnly" label="Open only" />
            <flux:button size="sm" variant="primary" icon="paper-airplane" wire:click="sendToAdvisor">
                Send to advisor
            </flux:button>
        </div>
    </div>

    <flux:separator class="mb-6" />

    @if ($this->findings->isNotEmpty())
        <div class="mb-6 flex flex-wrap items-center gap-4 rounded-lg border border-zinc-200 dark:border-zinc-800 p-4">
            <div>
                <flux:text size="sm" class="text-zinc-500">Items recommended</flux:text>
                <flux:heading size="lg">{{ $this->findings->count() }}</flux:heading>
            </div>
            <flux:separator vertical class="h-10" />
            <div>
                <flux:text size="sm" class="text-zinc-500">Estimated total</flux:text>
                <flux:heading size="lg">Rs {{ number_format($this->estimatedTotal, 2) }}</flux:heading>
            </div>
        </div>
    @endif

    <div class="space-y-2">
        @forelse ($this->findings as $f)
            <div wire:key="f-{{ $f->id }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs text-zinc-500">{{ $f->finding_no }}</span>
                            <flux:badge size="sm" :color="match ($f->status) {
                                'approved' => 'lime', 'rejected' => 'red',
                                'converted' => 'blue', default => 'amber',
                            }">{{ $TF::statuses()[$f->status] ?? $f->status }}</flux:badge>
                        </div>
                        <flux:text class="mt-1 font-medium">{{ $f->description }}</flux:text>
                        @if ($f->recommendation)
                            <flux:text size="sm" class="mt-0.5 text-zinc-500">{{ $f->recommendation }}</flux:text>
                        @endif
                        <flux:text size="sm" class="mt-1 text-zinc-400">
                            @if ($f->reportedBy){{ $f->reportedBy->name }}@endif
                            @if ($f->spare)· {{ $f->spare->name }}@endif
                            @if ($f->labour)· {{ $f->labour->name }}@endif
                        </flux:text>
                    </div>
                    <div class="shrink-0 text-right">
                        @if ($f->estimated_amount)
                            <flux:heading size="sm">Rs {{ number_format((float) $f->estimated_amount, 2) }}</flux:heading>
                        @endif
                        <flux:button size="xs" variant="ghost" icon="pencil" :href="route('technician-finding.edit', $f->id)" wire:navigate class="mt-1">Edit</flux:button>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-16 text-center">
                <flux:icon.clipboard-document-check class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-3">{{ $openOnly ? 'No open findings' : 'No findings yet' }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    {{ $openOnly ? 'Everything found has been decided on.' : 'Findings raised by technicians will appear here.' }}
                </flux:text>
            </div>
        @endforelse
    </div>

    @if ($this->findings->isNotEmpty())
        <div class="mt-6">
            <flux:heading size="sm" class="mb-2">Summary to read to the customer</flux:heading>
            <flux:textarea rows="10" readonly class:input="font-mono text-xs" :value="$this->summaryText" />
        </div>
    @endif
</div>
