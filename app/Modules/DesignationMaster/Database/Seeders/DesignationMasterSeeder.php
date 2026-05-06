<?php

namespace App\Modules\DesignationMaster\Database\Seeders;

use App\Modules\DesignationMaster\Models\DesignationMaster;
use Illuminate\Database\Seeder;

class DesignationMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            'ADMIN', 'SUPER ADMIN', 'IT ADMIN',
            'MECHANICAL ADVISOR', 'BODYSHOP ADVISOR', 'TYRE ADVISOR', 'ACCESSORIES ADVISOR', 'VA ADVISOR',
            'FLOOR INCHARGE', 'STORE INCHARGE', 'STORE EXECUTIVE',
            'CASHIER', 'ACCOUNTANT', 'HR',
            'TECHNICIAN', 'DRIVER', 'SECURITY GUARD', 'CRM',
        ];

        foreach ($real as $name) {
            DesignationMaster::firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
