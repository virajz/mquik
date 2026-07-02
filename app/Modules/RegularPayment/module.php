<?php

use App\Modules\RegularPayment\Models\RegularPayment;

return [
    'label' => 'Regular Payments',
    'description' => 'Payments to vendors — payment mode, cheque tracking, hold & cancellation, against purchase invoices.',
    'group' => 'Purchase',
    'icon' => 'banknotes',
    'permissions' => [
        'regular_payment.view',
        'regular_payment.create',
        'regular_payment.update',
        'regular_payment.delete',
    ],
    'searchable' => [
        'model' => RegularPayment::class,
        'route' => 'regular-payment.index',
    ],
];
