<?php

use App\Modules\VpoCancelRequest\Models\VpoCancelRequest;

return [
    'label' => 'VPO Cancel Requests',
    'description' => 'Ask a vendor to cancel a purchase order (full / partial / qty reduction); track response, charge terms and advance refund.',
    'group' => 'Purchase',
    'icon' => 'x-circle',
    'permissions' => [
        'vpo_cancel_request.view',
        'vpo_cancel_request.create',
        'vpo_cancel_request.update',
        'vpo_cancel_request.delete',
    ],
    'searchable' => [
        'model' => VpoCancelRequest::class,
        'route' => 'vpo-cancel-request.index',
    ],
];
