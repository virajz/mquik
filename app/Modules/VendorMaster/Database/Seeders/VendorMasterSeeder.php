<?php

namespace App\Modules\VendorMaster\Database\Seeders;

use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Illuminate\Database\Seeder;

class VendorMasterSeeder extends Seeder
{
    public function run(): void
    {
        $resolveType = fn (string $name) => VendorTypeMaster::query()
            ->whereLike('name', strtoupper($name).'%', caseSensitive: false)
            ->value('id');

        $vendors = [
            [
                'vendor_code' => 'VND-00001',
                'name' => 'BOSCH AUTHORISED DEALER',
                'type' => 'Spare Parts',
                'phone' => '9876543210',
                'state' => 'GUJARAT',
                'city' => 'AHMEDABAD',
            ],
            [
                'vendor_code' => 'VND-00002',
                'name' => 'SHARMA OUTSIDE LABOUR',
                'type' => 'OSL',
                'phone' => '9876543211',
            ],
            [
                'vendor_code' => 'VND-00003',
                'name' => 'MARUTI GENUINE PARTS',
                'type' => 'OEM',
                'phone' => '9876543212',
            ],
            [
                'vendor_code' => 'VND-00004',
                'name' => 'RTO AGENT KAMAL',
                'type' => 'Service',
                'phone' => '9876543213',
            ],
            [
                'vendor_code' => 'VND-00005',
                'name' => 'BHARTI AXA INSURANCE',
                'type' => 'Insurance',
                'phone' => '9876543214',
            ],
        ];

        foreach ($vendors as $v) {
            $vendor = VendorMaster::firstOrCreate(
                ['vendor_code' => $v['vendor_code']],
                [
                    'name' => $v['name'],
                    'phone' => $v['phone'],
                    'state' => $v['state'] ?? null,
                    'city' => $v['city'] ?? null,
                    'credit_days' => 30,
                    'credit_limit' => 50000,
                    'is_active' => true,
                ],
            );

            // Attach the vendor type via the many-to-many pivot
            $typeId = $resolveType($v['type']);
            if ($typeId !== null) {
                $vendor->vendorTypes()->syncWithoutDetaching([$typeId]);
            }
        }
    }
}
