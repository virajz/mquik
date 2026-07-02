<?php

use App\Modules\RegularReceipt\Models\RegularReceipt;

return [
    'label' => 'Regular Receipts',
    'description' => 'Customer payment receipts — payment mode, cheque tracking, differences and attachments.',
    'group' => 'Sales',
    'icon' => 'banknotes',
    'permissions' => [
        'regular_receipt.view',
        'regular_receipt.create',
        'regular_receipt.update',
        'regular_receipt.delete',
    ],
    'searchable' => [
        'model' => RegularReceipt::class,
        'route' => 'regular-receipt.index',
    ],
];
