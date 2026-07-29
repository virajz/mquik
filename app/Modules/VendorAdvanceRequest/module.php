<?php

use App\Modules\VendorAdvanceRequest\Models\VendorAdvanceRequest;

return [
    'label' => 'Vendor Advance Request',
    'description' => 'Request an advance to a vendor for special orders — reason, payment mode, document checklist and approval.',
    'group' => 'Finance',
    'icon' => 'arrow-up-right',
    'permissions' => [
        'vendor_advance_request.view',
        'vendor_advance_request.create',
        'vendor_advance_request.update',
        'vendor_advance_request.delete',
    ],
    'searchable' => [
        'model' => VendorAdvanceRequest::class,
        'route' => 'vendor-advance-request.index',
    ],
];
