<?php

namespace App\Modules\ChequeBounceReasonMaster\Database\Seeders;

use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use Illuminate\Database\Seeder;

class ChequeBounceReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Common reasons a cheque is returned by the bank.
        $real = [
            ['name' => 'INSUFFICIENT FUNDS',  'code' => 'INSF'],
            ['name' => 'SIGNATURE MISMATCH',  'code' => 'SIGN'],
            ['name' => 'STALE CHEQUE',        'code' => 'STALE'],
            ['name' => 'ACCOUNT CLOSED',      'code' => 'ACCL'],
        ];

        foreach ($real as $type) {
            ChequeBounceReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
