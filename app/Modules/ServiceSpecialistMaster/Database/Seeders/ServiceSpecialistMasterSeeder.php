<?php

namespace App\Modules\ServiceSpecialistMaster\Database\Seeders;

use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use Illuminate\Database\Seeder;

class ServiceSpecialistMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'DENTING',            'code' => 'DNT'],
            ['name' => 'PAINTING',           'code' => 'PNT'],
            ['name' => 'AC',                 'code' => 'AC'],
            ['name' => 'ELECTRICAL & WIRING', 'code' => 'ELW'],
            ['name' => 'ENGINE',             'code' => 'ENG'],
            ['name' => 'TRANSMISSION',       'code' => 'TRN'],
            ['name' => 'UPHOLSTERY',         'code' => 'UPH'],
            ['name' => 'WASHING',            'code' => 'WSH'],
            ['name' => 'DETAILING',          'code' => 'DTL'],
            ['name' => 'CAR SPA',            'code' => 'SPA'],
            ['name' => 'GEAR BOX',           'code' => 'GBX'],
            ['name' => 'REPOWERING',         'code' => 'RPW'],
            ['name' => 'ALLOY/RIM REFURBISH', 'code' => 'ARR'],
            ['name' => 'ACCESSORIES INSTALLATION', 'code' => 'ACC'],
            ['name' => 'LATHE WORK',         'code' => 'LTH'],
        ];

        foreach ($real as $type) {
            ServiceSpecialistMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
