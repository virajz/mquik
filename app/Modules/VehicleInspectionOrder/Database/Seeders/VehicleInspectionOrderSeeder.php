<?php

namespace App\Modules\VehicleInspectionOrder\Database\Seeders;

use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use Faker\Factory;
use Illuminate\Database\Seeder;

class VehicleInspectionOrderSeeder extends Seeder
{
    public function run(): void
    {
        // Demo inspection orders need Faker (dev-only) and are skipped on a
        // --no-dev server deploy where Faker is absent.
        if (! class_exists(Factory::class)) {
            return;
        }

        $jobCards = JobCard::query()->orderByDesc('id')->limit(4)->get();
        if ($jobCards->isEmpty()) {
            return;
        }

        $template = InspectionTemplateMaster::with(['items.group'])->where('is_active', true)->first();
        $bay = BayMaster::where('is_active', true)->first();
        $technician = EmployeeMaster::where('is_active', true)->first();
        $priorities = PriorityMaster::forScope('workshop')->pluck('id')->values();

        foreach ($jobCards as $i => $jobCard) {
            $state = match ($i % 3) {
                0 => 'wip',
                1 => 'completed',
                default => null,
            };

            $factory = VehicleInspectionOrder::factory();
            if ($state) {
                $factory = $factory->{$state}();
            }

            $order = $factory->create([
                'job_card_id' => $jobCard->id,
                'inspection_template_id' => $template?->id,
                'bay_id' => $bay?->id,
                'technician_id' => $technician?->id,
                'priority_id' => $priorities->isNotEmpty() ? $priorities[$i % $priorities->count()] : null,
            ]);

            if ($template) {
                $seq = 1;
                foreach ($template->items as $tplItem) {
                    $order->items()->create([
                        'inspection_item_id' => $tplItem->id,
                        'inspection_item_group_id' => $tplItem->inspection_item_group_id,
                        'label' => $tplItem->name,
                        'result' => ['ok', 'ia', 'fa', 'pending'][$seq % 4],
                        'sequence_no' => $seq++,
                    ]);
                }
            }
        }
    }
}
