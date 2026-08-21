<?php

namespace Database\Seeders;

use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Database\Seeder;

/**
 * The full set of Indian states and union territories.
 *
 * States are a closed, known list — letting a user type one at the counter only
 * produces "GUJRAT" alongside "GUJARAT". Seeding them means the top of the
 * region hierarchy is complete and never needs typing; cities and areas are the
 * genuine long tail and stay user-addable.
 */
class IndianStatesSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            'ANDHRA PRADESH', 'ARUNACHAL PRADESH', 'ASSAM', 'BIHAR', 'CHHATTISGARH',
            'GOA', 'GUJARAT', 'HARYANA', 'HIMACHAL PRADESH', 'JHARKHAND',
            'KARNATAKA', 'KERALA', 'MADHYA PRADESH', 'MAHARASHTRA', 'MANIPUR',
            'MEGHALAYA', 'MIZORAM', 'NAGALAND', 'ODISHA', 'PUNJAB',
            'RAJASTHAN', 'SIKKIM', 'TAMIL NADU', 'TELANGANA', 'TRIPURA',
            'UTTAR PRADESH', 'UTTARAKHAND', 'WEST BENGAL',
            // Union territories
            'ANDAMAN AND NICOBAR ISLANDS', 'CHANDIGARH',
            'DADRA AND NAGAR HAVELI AND DAMAN AND DIU', 'DELHI',
            'JAMMU AND KASHMIR', 'LADAKH', 'LAKSHADWEEP', 'PUDUCHERRY',
        ];

        foreach ($states as $name) {
            RegionMaster::firstOrCreate(
                ['kind' => 'state', 'name' => $name],
                ['is_active' => true],
            );
        }
    }
}
