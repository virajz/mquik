<div>
    <flux:modal name="job-description-master-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Job Description' : 'New Job Description' }}</flux:heading>
                <flux:subheading>Catalog of jobs the workshop performs — used by job cards and estimates.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input wire:model="name" label="Job Description" placeholder="e.g. ENGINE OIL CHANGE" required autofocus />
                    </div>
                    <flux:input wire:model="code" label="Code" placeholder="ENG-OIL" maxlength="20" class:input="font-mono uppercase tracking-wide" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="category" label="Category" variant="listbox" required>
                        @foreach ($categories as $c)
                            <flux:select.option :value="$c">{{ ucfirst($c) }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="service_type_id" label="Service Type" variant="listbox" placeholder="Optional" searchable>
                        <flux:select.option value="">— Skip —</flux:select.option>
                        @foreach ($serviceTypes as $st)
                            <flux:select.option :value="$st->id">{{ $st->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:field>
                    <flux:label>Standard Hours</flux:label>
                    <flux:input.group>
                        <flux:input wire:model="standard_hours" type="number" step="0.25" min="0" placeholder="0.50" inputmode="decimal" />
                        <flux:input.group.suffix>hrs</flux:input.group.suffix>
                    </flux:input.group>
                    <flux:description>Estimated time the technician should spend on this job.</flux:description>
                    <flux:error name="standard_hours" />
                </flux:field>

                <flux:textarea wire:model="notes" label="Internal Notes" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" description="Inactive descriptions won't appear in job-card and estimate dropdowns." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
