@use(App\Modules\FinalInspection\Models\FinalInspection)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Inspection Setup</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Job card, the technician's inspection reference, template and inspector.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model.live="job_card_id" variant="listbox" searchable label="Job Card" placeholder="Pick a job card…" required>
                @foreach ($this->jobCards as $jc)
                    <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="digital_inspection_id" variant="listbox" searchable clearable label="Technician Inspection Ref" placeholder="Digital inspection…">
                @foreach ($this->digitalInspections as $di)
                    <flux:select.option :value="$di->id" wire:key="di-{{ $di->id }}">{{ $di->inspection_no }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model.live="inspection_template_id" variant="listbox" searchable clearable label="Category Template" placeholder="Snapshot checkpoints…">
                @foreach ($this->templates as $t)
                    <flux:select.option :value="$t->id" wire:key="tpl-{{ $t->id }}">{{ $t->name }} ({{ strtoupper($t->applies_to) }})</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="inspector_id" variant="listbox" searchable clearable label="Inspector" placeholder="Pick an inspector…">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="ins-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="status" variant="listbox" label="Status" required>
                @foreach (FinalInspection::statuses() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>
</section>
