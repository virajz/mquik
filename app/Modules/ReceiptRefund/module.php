<?php

use App\Modules\ReceiptRefund\Models\ReceiptRefund;

return [
    'label' => 'Receipt Refunds',
    'description' => 'Customer refund requests & responses — overpayment or sales-return refunds, cheque tracking and approvals.',
    'group' => 'Sales',
    'icon' => 'receipt-refund',
    'permissions' => [
        'receipt_refund.view',
        'receipt_refund.create',
        'receipt_refund.update',
        'receipt_refund.delete',
    ],
    'searchable' => [
        'model' => ReceiptRefund::class,
        'route' => 'receipt-refund.index',
    ],
];
