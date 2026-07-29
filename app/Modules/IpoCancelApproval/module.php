<?php

use App\Modules\IpoCancelApproval\Models\IpoCancelApproval;

return [
    'label' => 'IPO Cancel Approval',
    'description' => 'Advisor requests to cancel specific un-issued parts on an internal part order — reason, impact, return handling and approval.',
    'group' => 'Inventory',
    'icon' => 'archive-box-x-mark',
    'permissions' => [
        'ipo_cancel_approval.view',
        'ipo_cancel_approval.create',
        'ipo_cancel_approval.update',
        'ipo_cancel_approval.delete',
    ],
    'searchable' => [
        'model' => IpoCancelApproval::class,
        'route' => 'ipo-cancel-approval.index',
    ],
];
