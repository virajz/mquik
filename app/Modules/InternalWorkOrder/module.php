<?php

use App\Modules\InternalWorkOrder\Models\InternalWorkOrder;

return [
    'label' => 'Internal Work Order',
    'description' => 'IWO — centralized register for internal complaints / requests / work orders across departments, with response, root-cause / corrective-action and TAT tracking (IWO- series).',
    'group' => 'HR',
    'icon' => 'clipboard-document-list',
    'permissions' => [
        'internal_work_order.view',
        'internal_work_order.create',
        'internal_work_order.update',
        'internal_work_order.delete',
    ],
    'searchable' => [
        'model' => InternalWorkOrder::class,
        'route' => 'internal-work-order.index',
    ],
];
