<?php

namespace App\Modules\DocumentCollection\Database\Factories;

use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentCollection\Models\DocumentCollection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentCollection>
 */
class DocumentCollectionFactory extends Factory
{
    protected $model = DocumentCollection::class;

    public function definition(): array
    {
        $customer = CustomerMaster::factory();

        return [
            'customer_id' => $customer,
            'customer_vehicle_id' => CustomerVehicleMaster::factory()->for($customer, 'customer'),
            'request_type' => $this->faker->randomElement(['customer', 'insurance_claim']),
            // The form's mandatory set — factory rows must survive an edit-and-save.
            'department_id' => WorkshopDepartmentMaster::factory(),
            // Lazy: runs after the department resolves, so the pairing is real.
            'service_type_id' => fn (array $attrs) => ServiceTypeMaster::factory()
                ->create(['workshop_department_id' => $attrs['department_id']])->id,
            'created_by_advisor_id' => EmployeeMaster::factory(),
            'purpose' => array_key_first(DocumentCollection::purposes()),
            'checklist_template_id' => ChecklistTemplateMaster::factory(),
            'verification_template_id' => ChecklistTemplateMaster::factory(),
            'status' => 'pending',
            'retention' => 'active',
            'requested_at' => now(),
            'entry_at' => now(),
        ];
    }

    public function insuranceClaim(): static
    {
        return $this->state(fn () => ['request_type' => 'insurance_claim']);
    }
}
