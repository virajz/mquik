<?php

use App\Modules\OutsideLabourInquiry\Models\OutsideLabourInquiry;

return [
    'label' => 'Outside Labour Inquiries',
    'description' => 'OLI — inquire outside contractors/vendors for charge and availability on work the workshop doesn\'t do in-house.',
    'group' => 'Workshop',
    'icon' => 'wrench-screwdriver',
    'permissions' => [
        'outside_labour_inquiry.view',
        'outside_labour_inquiry.create',
        'outside_labour_inquiry.update',
        'outside_labour_inquiry.delete',
    ],
    'searchable' => [
        'model' => OutsideLabourInquiry::class,
        'route' => 'outside-labour-inquiry.index',
    ],
];
