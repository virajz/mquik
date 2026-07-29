<?php

use App\Modules\VpoApproval\Models\VpoApproval;

return [
    'label' => 'VPO Approval',
    'description' => 'Admin approval for a vendor purchase order — stock bulk / odd item / high value / rate contract / emergency, line-by-line.',
    'group' => 'Purchase',
    'icon' => 'clipboard-document-check',
    'permissions' => [
        'vpo_approval.view',
        'vpo_approval.create',
        'vpo_approval.update',
        'vpo_approval.delete',
    ],
    'searchable' => [
        'model' => VpoApproval::class,
        'route' => 'vpo-approval.index',
    ],
];
