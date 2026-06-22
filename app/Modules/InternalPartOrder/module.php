<?php

use App\Modules\InternalPartOrder\Models\InternalPartOrder;

return [
    'label' => 'Internal Part Orders',
    'description' => 'IPO/IPR — advisor/floor requests parts from the store; store approves, picks, issues and accepts returns.',
    'group' => 'Inventory',
    'icon' => 'inbox-stack',
    'permissions' => [
        'internal_part_order.view',
        'internal_part_order.create',
        'internal_part_order.update',
        'internal_part_order.delete',
    ],
    'searchable' => [
        'model' => InternalPartOrder::class,
        'route' => 'internal-part-order.index',
    ],
];
