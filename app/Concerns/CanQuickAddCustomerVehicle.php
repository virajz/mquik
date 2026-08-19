<?php

namespace App\Concerns;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * Put an unknown car into the system without leaving the screen.
 *
 * At the gate a car turns up that nobody has ever booked — the guard has the
 * driver in front of them and needs the barrier up. Sending them to the customer
 * master, then the vehicle master, then back, is how walk-ins end up recorded as
 * bare number plates with no owner.
 *
 * This captures the five things actually knowable at the barrier — name, phone,
 * number plate, brand, model — and creates the customer and their vehicle
 * together. Everything else can be filled in later from the vehicle record.
 *
 * The consuming component says which property receives the new vehicle id.
 */
trait CanQuickAddCustomerVehicle
{
    /** @var array{name: string, phone: string, registration_no: string, brand_id: ?int, model_id: ?int} */
    public array $quickCV = [
        'name' => '',
        'phone' => '',
        'registration_no' => '',
        'brand_id' => null,
        'model_id' => null,
    ];

    public string $quickCVBrandSearch = '';

    public string $quickCVModelSearch = '';

    /** Property that should receive the new customer_vehicle id. */
    abstract protected function quickCustomerVehicleTargetProperty(): string;

    #[Computed]
    public function quickCVBrands()
    {
        return VehicleBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** Models are meaningless without their brand, so the list follows it. */
    #[Computed]
    public function quickCVModels()
    {
        if (! $this->quickCV['brand_id']) {
            return collect();
        }

        return VehicleModelMaster::query()
            ->where('is_active', true)
            ->where('brand_id', $this->quickCV['brand_id'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function updatedQuickCVBrandId(): void
    {
        $this->quickCV['model_id'] = null;
        unset($this->quickCVModels);
    }

    /** A brand nobody has entered yet — add it from the same box. */
    public function createQuickCVBrand(): void
    {
        $this->authorize('vehicle_brand_master.create');

        $name = strtoupper(trim($this->quickCVBrandSearch));
        if ($name === '') {
            return;
        }

        $brand = VehicleBrandMaster::firstOrCreate(['name' => $name], ['is_active' => true]);

        $this->quickCV['brand_id'] = $brand->id;
        $this->quickCV['model_id'] = null;
        $this->quickCVBrandSearch = '';

        unset($this->quickCVBrands, $this->quickCVModels);
    }

    public function createQuickCVModel(): void
    {
        $this->authorize('vehicle_model_master.create');

        if (! $this->quickCV['brand_id']) {
            $this->addError('quickCV.brand_id', 'Pick a brand before adding a model.');

            return;
        }

        $name = strtoupper(trim($this->quickCVModelSearch));
        if ($name === '') {
            return;
        }

        $model = VehicleModelMaster::firstOrCreate(
            ['brand_id' => $this->quickCV['brand_id'], 'name' => $name],
            ['is_active' => true],
        );

        $this->quickCV['model_id'] = $model->id;
        $this->quickCVModelSearch = '';

        unset($this->quickCVModels);
    }

    public function createQuickCustomerVehicle(): void
    {
        $this->authorize('customer_vehicle_master.create');

        $data = $this->validate([
            'quickCV.name' => ['required', 'string', 'max:255'],
            'quickCV.phone' => ['required', 'string', 'min:10', 'max:20'],
            'quickCV.registration_no' => ['required', 'string', 'max:20'],
            'quickCV.brand_id' => ['required', 'integer', Rule::exists('vehicle_brands', 'id')],
            'quickCV.model_id' => ['required', 'integer', Rule::exists('vehicle_models', 'id')],
        ], [], [
            'quickCV.name' => 'name',
            'quickCV.phone' => 'phone',
            'quickCV.registration_no' => 'vehicle number',
            'quickCV.brand_id' => 'brand',
            'quickCV.model_id' => 'model',
        ])['quickCV'];

        $plate = strtoupper(preg_replace('/\s+/', ' ', trim($data['registration_no'])));

        // Already on file — reuse it rather than creating a second record for
        // the same car, which is the whole problem this is meant to avoid.
        $existing = CustomerVehicleMaster::where('registration_no', $plate)->first();
        if ($existing) {
            $this->{$this->quickCustomerVehicleTargetProperty()} = $existing->id;
            $this->resetQuickCustomerVehicle();

            Flux::modal('customer-vehicle-quick-add')->close();
            Flux::toast(text: 'That number plate is already on file — linked to it.', variant: 'warning');

            return;
        }

        $vehicle = DB::transaction(function () use ($data, $plate) {
            // One phone is one customer; a returning owner brings a second car.
            $customer = CustomerMaster::firstOrCreate(
                ['phone' => preg_replace('/\D/', '', $data['phone'])],
                ['first_name' => strtoupper(trim($data['name'])), 'is_active' => true],
            );

            return CustomerVehicleMaster::create([
                'customer_id' => $customer->id,
                'registration_no' => $plate,
                'model_id' => $data['model_id'],
                'is_active' => true,
            ]);
        });

        $this->{$this->quickCustomerVehicleTargetProperty()} = $vehicle->id;

        $this->resetQuickCustomerVehicle();

        Flux::modal('customer-vehicle-quick-add')->close();
        Flux::toast(text: 'Vehicle added.', variant: 'success');
    }

    public function resetQuickCustomerVehicle(): void
    {
        $this->quickCV = [
            'name' => '',
            'phone' => '',
            'registration_no' => '',
            'brand_id' => null,
            'model_id' => null,
        ];
        $this->quickCVBrandSearch = '';
        $this->quickCVModelSearch = '';
    }
}
