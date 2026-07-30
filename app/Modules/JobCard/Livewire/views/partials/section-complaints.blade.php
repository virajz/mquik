<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
    <div>
        <flux:heading size="lg">Customer Complaints</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">What the customer reported. One row per distinct complaint.</flux:text>
    </div>
    <div class="space-y-3 min-w-0">
        <div class="flex justify-end">
            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addComplaint">Add complaint</flux:button>
        </div>

        @if (count($complaints) === 0)
            <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                No complaints captured yet. Click <span class="font-medium">Add complaint</span> to start.
            </div>
        @else
            @foreach ($complaints as $i => $row)
                <div wire:key="complaint-row-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_180px_120px_40px] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                    <flux:select
                        wire:model="complaints.{{ $i }}.standard_observation_id"
                        variant="listbox"
                        searchable
                        size="sm"
                        label="Complaint"
                        placeholder="Pick a complaint…"
                        required
                    >
                        @foreach ($this->standardObservations as $obs)
                            <flux:select.option :value="$obs->id" wire:key="obs-{{ $i }}-{{ $obs->id }}">{{ $obs->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="complaints.{{ $i }}.complaint_type_id" variant="listbox" searchable clearable size="sm" label="Category">
                        @foreach ($this->complaintTypes as $ct)
                            <flux:select.option :value="$ct->id" wire:key="ct-{{ $i }}-{{ $ct->id }}">{{ $ct->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="complaints.{{ $i }}.severity" variant="listbox" size="sm" label="Severity">
                        <flux:select.option value="low">Low</flux:select.option>
                        <flux:select.option value="medium">Medium</flux:select.option>
                        <flux:select.option value="high">High</flux:select.option>
                    </flux:select>
                    <flux:button
                        type="button"
                        size="sm"
                        variant="ghost"
                        icon="x-mark"
                        wire:click="removeComplaint({{ $i }})"
                        class="h-9!"
                    />
                </div>
                <flux:error name="complaints.{{ $i }}.standard_observation_id" />
            @endforeach
        @endif
    </div>
</section>
