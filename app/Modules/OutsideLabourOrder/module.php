<?php

use App\Modules\OutsideLabourOrder\Models\OutsideLabourOrder;

return [
    'label' => 'Outside Labour Orders',
    'description' => 'Finalize the outside vendor order for outside work and track service start.',
    'group' => 'Workshop',
    'icon' => 'clipboard-document-check',
    'permissions' => [
        'outside_labour_order.view',
        'outside_labour_order.create',
        'outside_labour_order.update',
        'outside_labour_order.delete',
    ],
    'searchable' => [
        'model' => OutsideLabourOrder::class,
        'route' => 'outside-labour-order.index',
    ],
];
