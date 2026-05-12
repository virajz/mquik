<?php

namespace App\Concerns;

use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * Customer quick-add wizard for any picker that selects a customer
 * (e.g. CustomerMaster Edit's `referred_by_customer_id`,
 *  CustomerVehicleMaster Edit's `customer_id`).
 *
 * Customer create is multi-field, so the picker rule defers to a +button + modal
 * (per the saved feedback rule). This trait owns the state + validation + create.
 *
 * Each consuming component must implement `quickCustomerTargetProperty()` to
 * tell the trait which property holds the FK that should receive the new id.
 *
 * View-side: render a `<flux:modal name="customer-quick-add">` with three inputs
 * bound to `quickCustomer.first_name`, `quickCustomer.phone`, `quickCustomer.business_type_id`,
 * and a Save button calling `createQuickCustomer`.
 */
trait CanQuickAddCustomer
{
    /** @var array{first_name: string, phone: string, business_type_id: ?int} */
    public array $quickCustomer = [
        'first_name' => '',
        'phone' => '',
        'business_type_id' => null,
    ];

    /** Returns the name of the public property that should be set to the new customer's id. */
    abstract protected function quickCustomerTargetProperty(): string;

    /** Active business types for the quick-add modal's Type picker. */
    #[Computed]
    public function quickCustomerBusinessTypes()
    {
        return BusinessTypeMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function createQuickCustomer(): void
    {
        $this->authorize('customer_master.create');

        // Use the component's own validate() so errors land in the Livewire error
        // bag (testable via assertHasErrors). The rules are scoped to the
        // `quickCustomer.*` array so they don't collide with the parent's rules().
        $validated = $this->validate([
            'quickCustomer.first_name' => ['required', 'string', 'max:255'],
            'quickCustomer.phone' => ['required', 'string', 'min:10', 'max:20'],
            'quickCustomer.business_type_id' => ['required', 'integer', Rule::exists('business_types', 'id')->where('is_active', true)],
        ], [], [
            'quickCustomer.first_name' => 'first name',
            'quickCustomer.phone' => 'phone',
            'quickCustomer.business_type_id' => 'type',
        ])['quickCustomer'];

        $customer = CustomerMaster::create([
            'first_name' => strtoupper(trim($validated['first_name'])),
            'phone' => $validated['phone'],
            'business_type_id' => $validated['business_type_id'],
            'is_active' => true,
        ]);

        $target = $this->quickCustomerTargetProperty();
        $this->{$target} = $customer->id;

        $this->resetQuickCustomer();

        Flux::toast(text: 'Customer "'.$customer->name.'" added.', variant: 'success');
        Flux::modal('customer-quick-add')->close();
    }

    /** Tests shouldn't need to reach in to reset state — but keep it simple if they do. */
    public function resetQuickCustomer(): void
    {
        $this->quickCustomer = [
            'first_name' => '',
            'phone' => '',
            'business_type_id' => null,
        ];
    }
}
