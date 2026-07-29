<?php

use App\Modules\PaymentRefund\Models\PaymentRefund;

return [
    'label' => 'Payment Refund',
    'description' => 'Record a vendor refund — excess / duplicate payment, cancelled PO, invoice revision or a purchase return.',
    'group' => 'Finance',
    'icon' => 'arrow-uturn-down',
    'permissions' => [
        'payment_refund.view',
        'payment_refund.create',
        'payment_refund.update',
        'payment_refund.delete',
    ],
    'searchable' => [
        'model' => PaymentRefund::class,
        'route' => 'payment-refund.index',
    ],
];
