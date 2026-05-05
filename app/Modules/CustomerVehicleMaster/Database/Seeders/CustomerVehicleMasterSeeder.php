<?php

namespace App\Modules\CustomerVehicleMaster\Database\Seeders;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use Illuminate\Database\Seeder;

class CustomerVehicleMasterSeeder extends Seeder
{
    public function run(): void
    {
        $customers = CustomerMaster::query()->limit(5)->get();
        $models = VehicleModelMaster::query()->limit(5)->get();
        $colors = VehicleColorMaster::query()->limit(5)->get();

        if ($customers->isEmpty() || $models->isEmpty()) {
            return; // Need customers + models first
        }

        foreach ($customers as $customer) {
            CustomerVehicleMaster::factory()
                ->state([
                    'customer_id' => $customer->id,
                    'model_id' => $models->random()->id,
                    'color_id' => $colors->isNotEmpty() ? $colors->random()->id : null,
                ])
                ->create();
        }
    }
}
