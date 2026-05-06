<?php

namespace App\Modules\UnitOfMeasureMaster\Database\Seeders;

use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Seeder;

class UnitOfMeasureMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Common units of measure used across spares, labour, consumables and inventory.
        $real = [
            ['name' => 'PIECES',       'code' => 'PCS'],
            ['name' => 'LITRES',       'code' => 'LTR'],
            ['name' => 'KILOGRAMS',    'code' => 'KG'],
            ['name' => 'GRAMS',        'code' => 'G'],
            ['name' => 'METRES',       'code' => 'MTR'],
            ['name' => 'SETS',         'code' => 'SET'],
            ['name' => 'BOXES',        'code' => 'BOX'],
            ['name' => 'NUMBERS',      'code' => 'NOS'],
            ['name' => 'MILLIMETRES',  'code' => 'MM'],
            ['name' => 'INCHES',       'code' => 'IN'],
        ];

        foreach ($real as $row) {
            UnitOfMeasureMaster::firstOrCreate(
                ['name' => $row['name']],
                $row + ['is_active' => true],
            );
        }
    }
}
