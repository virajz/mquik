<?php

use App\Modules\FinalWorkOrder\Models\FinalWorkOrder;

return [
    'label' => 'Final Work Orders',
    'description' => 'Official work order after estimate approval — technician + bay assignment, time tracking, item-wise results and evidence photos.',
    'group' => 'Workshop',
    'icon' => 'clipboard-document-check',
    'permissions' => [
        'final_work_order.view',
        'final_work_order.create',
        'final_work_order.update',
        'final_work_order.delete',
    ],
    'searchable' => [
        'model' => FinalWorkOrder::class,
        'route' => 'final-work-order.index',
    ],
];
