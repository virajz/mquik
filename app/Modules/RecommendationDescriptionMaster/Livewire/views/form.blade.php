<div>
    <flux:modal name="recommendation-description-master-form" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit recommendation description' : 'New recommendation description' }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId ? 'Update the details below.' : 'Fill in the details to create a new record.' }}
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:select wire:model.live="category_id" variant="listbox" searchable required
                    label="Category" placeholder="Pick a category…">
                    @foreach ($this->categories as $category)
                        <flux:select.option :value="$category->id" wire:key="rc-{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Narrowed to the chosen category, so the filing cannot lie. --}}
                <flux:select wire:model="sub_category_id" variant="listbox" searchable clearable
                    label="Sub category" :disabled="! $category_id"
                    :placeholder="$category_id ? 'Optional' : 'Pick a category first'">
                    @foreach ($this->subCategories as $sub)
                        <flux:select.option :value="$sub->id" wire:key="rsc-{{ $sub->id }}">{{ $sub->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="name" label="Description" required autofocus
                    placeholder="Replace front brake pads — worn below 3mm" />

                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model="code" label="Code" placeholder="Optional" />
                    <flux:input type="number" wire:model="sequence_no" min="0" max="9999" required
                        label="Order" description="Low numbers first." />
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the team should know" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active"
                    description="Inactive entries won't appear on the inspection sheet." />
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
