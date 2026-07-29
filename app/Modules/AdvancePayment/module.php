<?php

use App\Modules\AdvancePayment\Models\AdvancePayment;

return [
    'label' => 'Advance Payment Entry',
    'description' => 'Accounts records an advance payment made to a vendor — MQ/AP FY series, payment mode, cheque tracking, reversal.',
    'group' => 'Finance',
    'icon' => 'banknotes',
    'permissions' => [
        'advance_payment.view',
        'advance_payment.create',
        'advance_payment.update',
        'advance_payment.delete',
    ],
    'searchable' => [
        'model' => AdvancePayment::class,
        'route' => 'advance-payment.index',
    ],
];
