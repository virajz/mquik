<?php

namespace App\Modules\EmployeeMaster\Database\Seeders;

use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Faker\Factory;
use Illuminate\Database\Seeder;

class EmployeeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Sample employees need Faker (dev-only). On a --no-dev server deploy Faker
        // is absent, so skip — real staff come from MasterDataSeeder.
        if (! class_exists(Factory::class)) {
            return;
        }

        $resolve = fn (string $designation, string $department) => [
            'designation_id' => DesignationMaster::firstOrCreate(['name' => strtoupper($designation)], ['is_active' => true])->id,
            'department_id' => DepartmentMaster::firstOrCreate(['name' => strtoupper($department)], ['is_active' => true])->id,
        ];

        EmployeeMaster::factory()->count(3)->advisor()->create();
        EmployeeMaster::factory()->count(5)->technician()->create();
        EmployeeMaster::factory()->state($resolve('Cashier', 'Accounts'))->create();
        EmployeeMaster::factory()->state($resolve('Floor Incharge', 'Service'))->create();
        EmployeeMaster::factory()->state($resolve('Store Incharge', 'Stores'))->create();
    }
}
