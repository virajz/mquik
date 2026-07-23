<div>
    <flux:modal name="job-stage-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Job Stage' : 'New Job Stage' }}
                </flux:heading>
                <flux:subheading>
                    Lifecycle stages a job moves through — separate sequences for regular vs insurance jobs.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Stage Name"
                            placeholder="e.g. SURVEYOR INSPECTION"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="INS-SRV"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:select wire:model="track" variant="listbox" label="Track" required>
                            @foreach (\App\Modules\JobStageMaster\Models\JobStageMaster::tracks() as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:input
                        wire:model="sort_order"
                        type="number"
                        min="0"
                        label="Order"
                        placeholder="0"
                        description="Within the track."
                        class:input="text-right font-mono"
                    />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this job stage"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive stages won't appear in the Job Card stage picker."
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $editingId ? 'Save changes' : 'Create' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
