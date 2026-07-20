<?php

namespace App\Modules\TechnicianFinding\Database\Seeders;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use Faker\Factory;
use Illuminate\Database\Seeder;

class TechnicianFindingSeeder extends Seeder
{
    public function run(): void
    {
        // Demo findings need Faker (dev-only) and are skipped on a --no-dev
        // server deploy where Faker is absent.
        if (! class_exists(Factory::class)) {
            return;
        }

        $jobCards = JobCard::query()->orderByDesc('id')->limit(3)->get();
        if ($jobCards->isEmpty()) {
            return;
        }

        $spare = SpareMaster::where('is_active', true)->first();
        $employee = EmployeeMaster::where('is_active', true)->first();

        foreach ($jobCards as $i => $jobCard) {
            $order = VehicleInspectionOrder::where('job_card_id', $jobCard->id)->first();

            TechnicianFinding::factory()->create([
                'job_card_id' => $jobCard->id,
                'vehicle_inspection_order_id' => $order?->id,
                'spare_id' => $spare?->id,
                'reported_by_id' => $employee?->id,
                'description' => ['BRAKE DISC WORN', 'COOLANT HOSE LEAK', 'WIPER MOTOR FAULTY'][$i % 3],
                'quantity' => 1,
                'estimated_amount' => [1500, 800, 2200][$i % 3],
                'status' => [TechnicianFinding::STATUS_RECOMMENDED, TechnicianFinding::STATUS_APPROVED, TechnicianFinding::STATUS_REJECTED][$i % 3],
            ]);
        }
    }
}
