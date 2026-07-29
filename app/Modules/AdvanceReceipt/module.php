<?php

use App\Modules\AdvanceReceipt\Models\AdvanceReceipt;

return [
    'label' => 'Advance Receipt Entry',
    'description' => 'Cashier records an advance payment against a job card / estimate — MQ/AR FY series, payment mode, cheque tracking.',
    'group' => 'Finance',
    'icon' => 'receipt-percent',
    'permissions' => [
        'advance_receipt.view',
        'advance_receipt.create',
        'advance_receipt.update',
        'advance_receipt.delete',
    ],
    'searchable' => [
        'model' => AdvanceReceipt::class,
        'route' => 'advance-receipt.index',
    ],
];
