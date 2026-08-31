<div>
    <flux:modal name="recommendation-category-master-form" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit recommendation category' : 'New recommendation category' }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId ? 'Update the details below.' : 'Fill in the details to create a new record.' }}
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                {{-- Leave the parent empty for a Category; pick one to make this
                     a Sub Category of it. Categories nest one level, not many. --}}
                <flux:select wire:model.live="parent_id" variant="listbox" searchable clearable
                    label="Parent category" placeholder="None — this is a Category">
                    @foreach ($this->parents as $parent)
                        <flux:select.option :value="$parent->id" wire:key="pc-{{ $parent->id }}">{{ $parent->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="name" label="Name" required autofocus
                    :placeholder="$parent_id ? 'Sub category name' : 'Category name'" />

                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model="code" label="Code" placeholder="Optional" />
                    <flux:input type="number" wire:model="sequence_no" min="0" max="9999" required
                        label="Order" description="Low numbers first." />
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the team should know" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active"
                    description="Inactive entries won't appear when picking recommendations." />
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
