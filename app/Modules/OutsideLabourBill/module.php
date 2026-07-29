<?php

use App\Modules\OutsideLabourBill\Models\OutsideLabourBill;

return [
    'label' => 'Outside Labour Bill Verification',
    'description' => 'Receive an outside vendor/contractor invoice and verify rate / qty / job match before payment.',
    'group' => 'Workshop',
    'icon' => 'document-check',
    'permissions' => [
        'outside_labour_bill.view',
        'outside_labour_bill.create',
        'outside_labour_bill.update',
        'outside_labour_bill.delete',
    ],
    'searchable' => [
        'model' => OutsideLabourBill::class,
        'route' => 'outside-labour-bill.index',
    ],
];
