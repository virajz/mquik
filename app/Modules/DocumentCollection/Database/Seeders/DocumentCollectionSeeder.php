<?php

namespace App\Modules\DocumentCollection\Database\Seeders;

use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentCollection\Models\DocumentCollection;
use Illuminate\Database\Seeder;

class DocumentCollectionSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed if we have vehicles to attach to and none exist yet.
        if (DocumentCollection::query()->exists()) {
            return;
        }

        $vehicles = CustomerVehicleMaster::query()->with('customer')->limit(3)->get();
        if ($vehicles->isEmpty()) {
            return;
        }

        $docTemplate = ChecklistTemplateMaster::where('name', 'INSURANCE CLAIM DOCUMENTS')->first();
        $verifyTemplate = ChecklistTemplateMaster::where('name', 'CLAIM VERIFICATION')->first();

        foreach ($vehicles as $vehicle) {
            $dc = DocumentCollection::create([
                'customer_id' => $vehicle->customer_id,
                'customer_vehicle_id' => $vehicle->id,
                'request_type' => 'insurance_claim',
                'status' => 'requested',
                'retention' => 'active',
                'checklist_template_id' => $docTemplate?->id,
                'verification_template_id' => $verifyTemplate?->id,
                'requested_at' => now(),
                'entry_at' => now(),
            ]);

            foreach ((array) ($docTemplate?->items ?? []) as $i => $item) {
                $dc->items()->create([
                    'label' => strtoupper((string) ($item['label'] ?? '')),
                    'is_required' => (bool) ($item['is_required'] ?? false),
                    'status' => 'pending',
                    'sequence_no' => $i + 1,
                ]);
            }

            foreach ((array) ($verifyTemplate?->items ?? []) as $i => $item) {
                $dc->verifications()->create([
                    'label' => strtoupper((string) ($item['label'] ?? '')),
                    'is_verified' => false,
                    'sequence_no' => $i + 1,
                ]);
            }
        }
    }
}
