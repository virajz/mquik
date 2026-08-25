{{--
    Customer quick-add modal — included by any parent component that uses
    `CanQuickAddCustomer`. The trait provides `$quickCustomer.*` properties,
    `$this->quickCustomerBusinessTypes` computed, and the `createQuickCustomer`
    action. Modal name is fixed so the +button can call $flux.modal('customer-quick-add').show().
--}}
<flux:modal name="customer-quick-add" class="md:w-md">
    <form wire:submit.prevent="createQuickCustomer" novalidate class="space-y-5">
        <div>
            <flux:heading size="lg">Quick add Customer</flux:heading>
            <flux:subheading>Just the essentials. Fill the rest later from the Customers page.</flux:subheading>
        </div>

        <flux:input
            wire:model="quickCustomer.first_name"
            label="First Name"
            placeholder="First or company name"
            autofocus
            required
        />

        <flux:field>
            <flux:label>Phone <span class="text-red-500">*</span></flux:label>
            <flux:input.group>
                <flux:input.group.prefix>+91</flux:input.group.prefix>
                <flux:input
                    wire:model="quickCustomer.phone"
                    mask="99999 99999"
                    placeholder="98765 43210"
                    inputmode="numeric"
                    required
                />
            </flux:input.group>
            <flux:error name="quickCustomer.phone" />
        </flux:field>

        <flux:select
            wire:model="quickCustomer.business_type_id"
            variant="listbox"
            searchable
            label="Type"
            required
        >
            @foreach ($this->quickCustomerBusinessTypes as $bt)
                <flux:select.option :value="$bt->id">{{ $bt->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="quickCustomer.business_type_id" />
        <flux:error name="quickCustomer.first_name" />

        <div class="flex justify-end gap-2 pt-2">
            <flux:modal.close>
                <flux:button variant="ghost" type="button">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" icon="check">Add</flux:button>
        </div>
    </form>
</flux:modal>
