<?php

namespace App\Modules\HsnMaster\Database\Seeders;

use App\Modules\HsnMaster\Models\HsnMaster;
use Illuminate\Database\Seeder;

class HsnMasterSeeder extends Seeder
{
    public function run(): void
    {
        // The codes an automotive workshop actually bills against. HSN for the
        // goods it sells, SAC for the labour it charges.
        $real = [
            ['code' => '8708', 'name' => 'PARTS AND ACCESSORIES OF MOTOR VEHICLES', 'kind' => 'hsn', 'gst' => 28],
            ['code' => '87082900', 'name' => 'BODY PARTS AND ACCESSORIES', 'kind' => 'hsn', 'gst' => 28],
            ['code' => '87083000', 'name' => 'BRAKES AND SERVO-BRAKES', 'kind' => 'hsn', 'gst' => 28],
            ['code' => '87084000', 'name' => 'GEAR BOXES', 'kind' => 'hsn', 'gst' => 28],
            ['code' => '87088000', 'name' => 'SUSPENSION SYSTEMS AND SHOCK ABSORBERS', 'kind' => 'hsn', 'gst' => 28],
            ['code' => '4011', 'name' => 'NEW PNEUMATIC TYRES OF RUBBER', 'kind' => 'hsn', 'gst' => 28],
            ['code' => '40111000', 'name' => 'TYRES FOR MOTOR CARS', 'kind' => 'hsn', 'gst' => 28],
            ['code' => '2710', 'name' => 'PETROLEUM OILS AND LUBRICANTS', 'kind' => 'hsn', 'gst' => 18],
            ['code' => '27101980', 'name' => 'LUBRICATING OILS AND GREASES', 'kind' => 'hsn', 'gst' => 18],
            ['code' => '8507', 'name' => 'ELECTRIC ACCUMULATORS / BATTERIES', 'kind' => 'hsn', 'gst' => 28],
            ['code' => '8511', 'name' => 'ELECTRICAL IGNITION AND STARTING EQUIPMENT', 'kind' => 'hsn', 'gst' => 28],
            ['code' => '8512', 'name' => 'LIGHTING AND SIGNALLING EQUIPMENT', 'kind' => 'hsn', 'gst' => 28],
            ['code' => '3208', 'name' => 'PAINTS AND VARNISHES', 'kind' => 'hsn', 'gst' => 18],
            ['code' => '998714', 'name' => 'MAINTENANCE AND REPAIR OF MOTOR VEHICLES', 'kind' => 'sac', 'gst' => 18],
            ['code' => '998729', 'name' => 'MAINTENANCE AND REPAIR OF OTHER GOODS', 'kind' => 'sac', 'gst' => 18],
            ['code' => '996511', 'name' => 'ROAD TRANSPORT SERVICES OF GOODS', 'kind' => 'sac', 'gst' => 18],
            ['code' => '998715', 'name' => 'WASHING, CLEANING AND PAINTING SERVICES', 'kind' => 'sac', 'gst' => 18],
        ];

        foreach ($real as $row) {
            HsnMaster::firstOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'kind' => $row['kind'],
                    'gst_percent' => $row['gst'],
                    'is_active' => true,
                ],
            );
        }
    }
}
