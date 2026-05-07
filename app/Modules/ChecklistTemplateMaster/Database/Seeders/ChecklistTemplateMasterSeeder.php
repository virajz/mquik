<?php

namespace App\Modules\ChecklistTemplateMaster\Database\Seeders;

use App\Modules\ChecklistGroupMaster\Models\ChecklistGroupMaster;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use Illuminate\Database\Seeder;

class ChecklistTemplateMasterSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'DOCUMENT COLLECTION STANDARD',
                'code' => 'DOC-STD',
                'group_name' => 'DOCUMENT COLLECTION',
                'applies_to' => 'claim',
                'items' => [
                    ['label' => 'RC COPY', 'is_required' => true],
                    ['label' => 'DL COPY', 'is_required' => true],
                    ['label' => 'AADHAR COPY', 'is_required' => true],
                    ['label' => 'PAN COPY', 'is_required' => true],
                    ['label' => 'INSURANCE POLICY COPY', 'is_required' => true],
                    ['label' => 'CANCEL CHEQUE', 'is_required' => false],
                ],
            ],
            [
                'name' => 'PRE-DELIVERY VEHICLE WASH',
                'code' => 'PD-WASH',
                'group_name' => 'PRE-DELIVERY',
                'applies_to' => 'delivery',
                'items' => [
                    ['label' => 'VEHICLE WASHED', 'is_required' => true],
                    ['label' => 'FLOOR MATS PLACED', 'is_required' => true],
                    ['label' => 'FUEL TOPPED UP', 'is_required' => false],
                    ['label' => 'INTERIORS CLEANED', 'is_required' => true],
                    ['label' => 'KEYS HANDED OVER', 'is_required' => true],
                    ['label' => 'DOCUMENTS RETURNED', 'is_required' => true],
                ],
            ],
            [
                'name' => 'SAFETY DAILY CHECK',
                'code' => 'SAF-DAY',
                'group_name' => 'SAFETY',
                'applies_to' => 'generic',
                'items' => [
                    ['label' => 'PPE WORN', 'is_required' => true],
                    ['label' => 'FIRE EXTINGUISHER ACCESSIBLE', 'is_required' => true],
                    ['label' => 'FIRST AID KIT STOCKED', 'is_required' => true],
                    ['label' => 'EMERGENCY EXITS CLEAR', 'is_required' => true],
                ],
            ],
            [
                'name' => 'JOB CARD CLOSURE',
                'code' => 'JCC-STD',
                'group_name' => 'JOB CARD CLOSURE',
                'applies_to' => 'job_card',
                'items' => [
                    ['label' => 'ALL TASKS COMPLETED', 'is_required' => true],
                    ['label' => 'CUSTOMER NOTIFIED', 'is_required' => true],
                    ['label' => 'PAYMENT CLEARED', 'is_required' => true],
                    ['label' => 'INVOICE GENERATED', 'is_required' => true],
                    ['label' => 'FEEDBACK COLLECTED', 'is_required' => false],
                ],
            ],
        ];

        foreach ($templates as $tpl) {
            $group = ChecklistGroupMaster::firstOrCreate(
                ['name' => $tpl['group_name']],
                ['is_active' => true],
            );

            ChecklistTemplateMaster::firstOrCreate(
                ['name' => $tpl['name']],
                [
                    'code' => $tpl['code'],
                    'checklist_group_id' => $group->id,
                    'applies_to' => $tpl['applies_to'],
                    'items' => $tpl['items'],
                    'is_active' => true,
                ],
            );
        }
    }
}
