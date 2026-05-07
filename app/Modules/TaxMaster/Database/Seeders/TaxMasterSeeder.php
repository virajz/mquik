<?php

namespace App\Modules\TaxMaster\Database\Seeders;

use App\Modules\TaxMaster\Models\TaxMaster;
use Illuminate\Database\Seeder;

class TaxMasterSeeder extends Seeder
{
    public function run(): void
    {
        $taxes = [
            ['name' => 'NIL RATED',                          'code' => 'NIL',       'hsn_sac' => null,     'gst_percent' => '0.00',  'cess_percent' => '0.00'],
            ['name' => 'GST 5%',                             'code' => 'GST5',      'hsn_sac' => null,     'gst_percent' => '5.00',  'cess_percent' => '0.00'],
            ['name' => 'GST 12%',                            'code' => 'GST12',     'hsn_sac' => null,     'gst_percent' => '12.00', 'cess_percent' => '0.00'],
            ['name' => 'GST 18%',                            'code' => 'GST18',     'hsn_sac' => null,     'gst_percent' => '18.00', 'cess_percent' => '0.00'],
            ['name' => 'GST 28%',                            'code' => 'GST28',     'hsn_sac' => null,     'gst_percent' => '28.00', 'cess_percent' => '0.00'],
            ['name' => 'GST 28% + CESS 17% (LUXURY CARS)',   'code' => 'GST28C17',  'hsn_sac' => '8703',   'gst_percent' => '28.00', 'cess_percent' => '17.00'],
            ['name' => 'GST 28% + CESS 22% (LUXURY SUV)',    'code' => 'GST28C22',  'hsn_sac' => '8703',   'gst_percent' => '28.00', 'cess_percent' => '22.00'],
            ['name' => 'LABOUR SAC 9987',                    'code' => 'SAC9987',   'hsn_sac' => '9987',   'gst_percent' => '18.00', 'cess_percent' => '0.00'],
            ['name' => 'LABOUR SAC 998729 (VEHICLE SERVICE)', 'code' => 'SAC998729', 'hsn_sac' => '998729', 'gst_percent' => '18.00', 'cess_percent' => '0.00'],
        ];

        foreach ($taxes as $tax) {
            TaxMaster::firstOrCreate(
                ['code' => $tax['code']],
                [
                    'name' => $tax['name'],
                    'hsn_sac' => $tax['hsn_sac'],
                    'gst_percent' => $tax['gst_percent'],
                    'cess_percent' => $tax['cess_percent'],
                    'is_active' => true,
                ],
            );
        }
    }
}
