<?php

namespace App\Modules\DistanceSlabMaster\Database\Seeders;

use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use Illuminate\Database\Seeder;

class DistanceSlabMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Pickup/drop charge bands. The top slab is open-ended (null max_km).
        $real = [
            ['name' => '0-5 KM',   'code' => 'D1', 'min_km' => 0,  'max_km' => 5,    'charge_amount' => 200],
            ['name' => '6-15 KM',  'code' => 'D2', 'min_km' => 6,  'max_km' => 15,   'charge_amount' => 300],
            ['name' => '16-25 KM', 'code' => 'D3', 'min_km' => 16, 'max_km' => 25,   'charge_amount' => 400],
            ['name' => '26 KM +',  'code' => 'D4', 'min_km' => 26, 'max_km' => null, 'charge_amount' => 500],
        ];

        foreach ($real as $row) {
            DistanceSlabMaster::firstOrCreate(
                ['name' => $row['name']],
                [
                    'code' => $row['code'],
                    'min_km' => $row['min_km'],
                    'max_km' => $row['max_km'],
                    'charge_amount' => $row['charge_amount'],
                    'is_active' => true,
                ],
            );
        }
    }
}
