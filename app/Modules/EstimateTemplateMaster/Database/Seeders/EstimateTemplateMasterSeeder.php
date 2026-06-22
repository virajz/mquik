<?php

namespace App\Modules\EstimateTemplateMaster\Database\Seeders;

use App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Seeder;

class EstimateTemplateMasterSeeder extends Seeder
{
    public function run(): void
    {
        $template = EstimateTemplateMaster::firstOrCreate(
            ['name' => 'PMS BASIC'],
            ['code' => 'PMS-B', 'is_active' => true, 'notes' => 'Standard periodic maintenance estimate.'],
        );

        if ($template->items()->exists()) {
            return;
        }

        $seq = 1;
        foreach (SpareMaster::where('is_active', true)->limit(3)->get() as $spare) {
            $template->items()->create([
                'line_type' => 'spare',
                'spare_id' => $spare->id,
                'default_qty' => 1,
                'sequence_no' => $seq++,
            ]);
        }
        foreach (LabourMaster::where('is_active', true)->limit(2)->get() as $labour) {
            $template->items()->create([
                'line_type' => 'labour',
                'labour_id' => $labour->id,
                'default_qty' => 1,
                'sequence_no' => $seq++,
            ]);
        }
    }
}
