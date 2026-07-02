<?php

return [
    [
        'mode' => 'setup',
        'group' => 'Purchase',
        'label' => 'Payment Cancellation Reasons',
        'icon' => 'x-circle',
        'route' => 'payment-cancellation-reason-master.index',
        'permission' => 'payment_cancellation_reason_master.view',
        'order' => 20,
    ],
];
