<?php

use App\Modules\ConsumableApproval\Models\ConsumableApproval;

return [
    'label' => 'Consumable Approval',
    'description' => 'Request approval to book a consumable / labour loss or damage against a job card; approve, hold or reject.',
    'group' => 'Inventory',
    'icon' => 'beaker',
    'permissions' => [
        'consumable_approval.view',
        'consumable_approval.create',
        'consumable_approval.update',
        'consumable_approval.delete',
    ],
    'searchable' => [
        'model' => ConsumableApproval::class,
        'route' => 'consumable-approval.index',
    ],
];
