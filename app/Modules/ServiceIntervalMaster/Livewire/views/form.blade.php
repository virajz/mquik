<div>
    <flux:modal name="service-interval-master-form" :dismissible="false" class="md:w-lg">
        <form wire:submit="save" novalidate class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Service Interval' : 'New Service Interval' }}</flux:heading>
                <flux:subheading>How often this service falls due — by time, by distance, or both.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <flux:input wire:model="name" label="Service" placeholder="e.g. ENGINE OIL REPLACE"
                description="Must match the wording used on job cards for it to be recognised." required autofocus />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="interval_months" type="number" min="1" max="120"
                    label="Every (months)" placeholder="e.g. 6" class:input="text-right font-mono" />
                <flux:input wire:model="interval_km" type="number" min="100" max="500000"
                    label="Every (km)" placeholder="e.g. 10000" class:input="text-right font-mono" />
            </div>
            <flux:text size="sm" class="-mt-2 text-zinc-500">
                Whichever comes first marks the service due. Leave both blank for jobs that are never overdue, like a car wash.
            </flux:text>

            <flux:textarea wire:model="description" rows="2" label="Notes" placeholder="Optional" />

            <flux:separator variant="subtle" />

            <flux:switch wire:model="is_active" label="Active" description="Inactive intervals are ignored when working out what's due." />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
