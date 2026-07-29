<?php

use App\Modules\OutsideLabourReturn\Models\OutsideLabourReturn;

return [
    'label' => 'Outside Labour Return / Warranty',
    'description' => 'Single parts + labour return / warranty claim to an outside vendor — rework, replacement, counter-proposal or amount settlement.',
    'group' => 'Workshop',
    'icon' => 'arrow-uturn-left',
    'permissions' => [
        'outside_labour_return.view',
        'outside_labour_return.create',
        'outside_labour_return.update',
        'outside_labour_return.delete',
    ],
    'searchable' => [
        'model' => OutsideLabourReturn::class,
        'route' => 'outside-labour-return.index',
    ],
];
