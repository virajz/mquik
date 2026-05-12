<?php

namespace App\Modules\CustomerVehicleMaster\Livewire;

use App\Concerns\CanQuickAddCustomer;
use App\Concerns\CanQuickAddVehicle;
use App\Concerns\HasQuickCreate;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Customer Vehicle')]
class Edit extends Component
{
    use CanQuickAddCustomer;
    use CanQuickAddVehicle;
    use HasQuickCreate;

    public ?int $editingId = null;

    public ?int $customer_id = null;

    /** Single picker — picking a variant fixes brand+model+variant. model_id is derived on save. */
    public ?int $variant_id = null;

    public ?int $color_id = null;

    /** Search string for the Color combobox — also used as the inline "Create" name. */
    public string $colorSearch = '';

    public string $registration_no = '';

    /** private | commercial | government | bh_series | military | other */
    public string $number_plate_type = 'private';

    public ?int $year_of_manufacture = null;

    public ?string $vin = null;

    public ?string $engine_no = null;

    public ?int $odometer_km = null;

    public bool $is_active = true;

    public ?string $notes = null;

    /** @return array<string, string> */
    public static function plateTypes(): array
    {
        return [
            'private' => 'Private',
            'commercial' => 'Commercial',
            'government' => 'Government',
            'bh_series' => 'BH Series',
            'military' => 'Military',
            'other' => 'Other',
        ];
    }

    public function mount(?CustomerVehicleMaster $customer_vehicle = null): void
    {
        if ($customer_vehicle && $customer_vehicle->exists) {
            $this->load($customer_vehicle);
        }
    }

    protected function load(CustomerVehicleMaster $vehicle): void
    {
        $this->editingId = $vehicle->id;
        $this->customer_id = $vehicle->customer_id;
        $this->variant_id = $vehicle->variant_id;
        $this->color_id = $vehicle->color_id;
        $this->registration_no = $vehicle->registration_no;
        $this->number_plate_type = $vehicle->number_plate_type ?? 'private';
        $this->year_of_manufacture = $vehicle->year_of_manufacture;
        $this->vin = $vehicle->vin;
        $this->engine_no = $vehicle->engine_no;
        $this->odometer_km = $vehicle->odometer_km;
        $this->is_active = $vehicle->is_active;
        $this->notes = $vehicle->notes;
    }

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'variant_id' => ['required', 'integer', Rule::exists('vehicle_variants', 'id')->where('is_active', true)],
            'color_id' => ['nullable', 'integer', Rule::exists('vehicle_colors', 'id')->where('is_active', true)],
            'registration_no' => [
                'required', 'string', 'max:20',
                // Indian plates: AB12CD1234 (1-3 mid letters) OR 12BH1234AA.
                'regex:/^([A-Z]{2}[0-9]{2}[A-Z]{1,3}[0-9]{4}|[0-9]{2}BH[0-9]{4}[A-Z]{2})$/',
                Rule::unique('customer_vehicles', 'registration_no')->ignore($this->editingId),
            ],
            'number_plate_type' => ['required', 'string', Rule::in(array_keys(self::plateTypes()))],
            'year_of_manufacture' => ['nullable', 'integer', 'min:1980', 'max:'.((int) date('Y') + 1)],
            'vin' => [
                'nullable', 'string', 'size:17',
                Rule::unique('customer_vehicles', 'vin')->ignore($this->editingId),
            ],
            'engine_no' => ['nullable', 'string', 'max:30'],
            'odometer_km' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'registration_no.regex' => 'Registration must look like GJ05RH4816 (state series) or 24BH1234AA (BH series).',
        ];
    }

    #[Computed]
    public function customers()
    {
        return CustomerMaster::query()
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'phone']);
    }

    /**
     * One unified list — every active variant with brand+model context pre-loaded.
     * Picking one fixes the whole brand→model→variant chain.
     */
    #[Computed]
    public function vehicles()
    {
        return VehicleVariantMaster::query()
            ->with('model.brand:id,name')
            ->where('is_active', true)
            ->whereHas('model', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'model_id', 'name', 'year', 'fuel_type', 'transmission'])
            ->map(function ($v) {
                $bits = array_filter([
                    $v->year,
                    $v->fuel_type ? ucfirst($v->fuel_type) : null,
                    $v->transmission ? strtoupper($v->transmission) : null,
                ]);
                $tail = $bits ? ' • '.implode(' • ', $bits) : '';

                return (object) [
                    'id' => $v->id,
                    'label' => trim(($v->model?->brand?->name ?? '').' '.($v->model?->name ?? '').' — '.$v->name.$tail),
                ];
            });
    }

    #[Computed]
    public function colors()
    {
        return VehicleColorMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'hex_code']);
    }

    /** Customer quick-add (CanQuickAddCustomer trait) writes the new id back to the customer picker. */
    protected function quickCustomerTargetProperty(): string
    {
        return 'customer_id';
    }

    /** Vehicle quick-add (CanQuickAddVehicle trait) writes the new variant id back to the vehicle picker. */
    protected function quickVehicleTargetProperty(): string
    {
        return 'variant_id';
    }

    public function createColor(): void
    {
        $this->quickCreate(
            modelClass: VehicleColorMaster::class,
            targetProperty: 'color_id',
            searchProperty: 'colorSearch',
            permission: 'vehicle_color_master.create',
            label: 'Vehicle color',
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'customer_vehicle_master.update' : 'customer_vehicle_master.create');

        // Strip whitespace from plate before validation so paste-with-spaces doesn't trip the regex.
        $this->registration_no = strtoupper(preg_replace('/\s+/', '', (string) $this->registration_no) ?? '');

        $data = $this->validate();

        // Derive model_id from the picked variant — keeps the column in sync without exposing it in the UI.
        $variant = VehicleVariantMaster::findOrFail($this->variant_id);
        $data['model_id'] = $variant->model_id;

        foreach (['registration_no', 'vin', 'engine_no', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $vehicle = DB::transaction(function () use ($data) {
            if ($this->editingId) {
                $v = CustomerVehicleMaster::findOrFail($this->editingId);
                $v->update($data);
            } else {
                $v = CustomerVehicleMaster::create($data);
                $this->editingId = $v->id;
            }

            return $v;
        });

        Flux::toast(
            text: 'Vehicle #'.$vehicle->id.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('customer-vehicle-master.index');
    }

    public function render()
    {
        return view('customer-vehicle-master::edit');
    }
}
