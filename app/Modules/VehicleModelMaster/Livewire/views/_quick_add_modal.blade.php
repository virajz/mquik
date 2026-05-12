{{--
    Vehicle Model quick-add modal — included by any parent component using
    `CanQuickAddModel`. Brand + Segment pickers inside the wizard support
    inline create-option for new brands/segments.
--}}
<flux:modal name="vehicle-model-quick-add" class="md:w-md">
    <form wire:submit.prevent="createQuickModel" class="space-y-5">
        <div>
            <flux:heading size="lg">Quick add Model</flux:heading>
            <flux:subheading>Pick a brand, type the model name. Add details later from the Models page.</flux:subheading>
        </div>

        <flux:select
            wire:model="quickModel.brand_id"
            variant="combobox"
            label="Brand"
            required
        >
            <x-slot name="input">
                <flux:select.input wire:model="quickModelBrandSearch" placeholder="Pick or type to add…" />
            </x-slot>
            @foreach ($this->quickModelBrands as $b)
                <flux:select.option :value="$b->id" wire:key="qmb-{{ $b->id }}">{{ $b->name }}</flux:select.option>
            @endforeach
            @can('vehicle_brand_master.create')
                <flux:select.option.create wire:click="createQuickModelBrand" min-length="2">
                    Create "<span wire:text="quickModelBrandSearch"></span>"
                </flux:select.option.create>
            @endcan
        </flux:select>
        <flux:error name="quickModel.brand_id" />

        <flux:input
            wire:model="quickModel.name"
            label="Model Name"
            placeholder="e.g. SWIFT"
            required
        />
        <flux:error name="quickModel.name" />

        <flux:select
            wire:model="quickModel.vehicle_segment_id"
            variant="combobox"
            label="Segment"
            clearable
        >
            <x-slot name="input">
                <flux:select.input wire:model="quickModelSegmentSearch" placeholder="Pick or type to add…" />
            </x-slot>
            @foreach ($this->quickModelSegments as $s)
                <flux:select.option :value="$s->id" wire:key="qms-{{ $s->id }}">{{ $s->name }}</flux:select.option>
            @endforeach
            @can('vehicle_segment_master.create')
                <flux:select.option.create wire:click="createQuickModelSegment" min-length="2">
                    Create "<span wire:text="quickModelSegmentSearch"></span>"
                </flux:select.option.create>
            @endcan
        </flux:select>
        <flux:error name="quickModel.vehicle_segment_id" />

        <div class="flex justify-end gap-2 pt-2">
            <flux:modal.close>
                <flux:button variant="ghost" type="button">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" icon="check">Add</flux:button>
        </div>
    </form>
</flux:modal>
