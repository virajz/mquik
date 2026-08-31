<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Workshop settings') }}</flux:heading>

    <x-settings.layout :heading="__('Workshop')" :subheading="__('Behaviour you can tune without a deploy')">
        <form wire:submit="save" novalidate class="my-6 w-full space-y-6">
            <div>
                <flux:heading size="sm">{{ __('Service history') }}</flux:heading>
                <flux:subheading>
                    The panel on a job card showing what a vehicle has had done. Per-service due intervals live in
                    <flux:link :href="route('service-interval-master.index')" wire:navigate>Service Intervals</flux:link>.
                </flux:subheading>
            </div>

            <flux:select wire:model="sortMode" variant="listbox" :label="__('Order services by')"
                description="What a mechanic sees at the top of the list.">
                @foreach ($this->sortModes() as $key => $label)
                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="servicesShown" type="number" min="1" max="50"
                :label="__('Services listed')" description="Rows shown under “Last done”." required />

            <flux:input wire:model="defaultOverdueMonths" type="number" min="1" max="120"
                :label="__('Fallback overdue (months)')"
                description="Used only when a service has no interval of its own. Leave blank to flag nothing." />

            <flux:input wire:model="visitsScanned" type="number" min="10" max="500"
                :label="__('Past visits scanned')" description="How far back to look when building the list." required />

            <flux:input wire:model="visitsListed" type="number" min="5" max="200"
                :label="__('Past visits listed')" description="Rows shown under the filter box." required />

            <flux:separator variant="subtle" />

            <div>
                <flux:heading size="sm">{{ __('Security') }}</flux:heading>
                <flux:subheading>How long a screen can sit idle before it signs itself out.</flux:subheading>
            </div>

            <flux:input wire:model="sessionLifetime" type="number" min="5" max="480"
                :label="__('Sign out after (minutes idle)')"
                description="Applies to everyone. Shared floor terminals are the reason to keep this short."
                required />

            <flux:separator variant="subtle" />

            <div>
                <flux:heading size="sm">{{ __('Job Card Terms & Conditions') }}</flux:heading>
                <flux:subheading>What a customer — or their reference — accepts on the job card. Edit it here as policy changes; it applies to cards accepted from now on.</flux:subheading>
            </div>

            <flux:textarea wire:model="jobCardTerms" rows="6"
                :label="__('Terms text')"
                placeholder="The workshop is authorised to carry out the repairs listed…" />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </x-settings.layout>
</section>
