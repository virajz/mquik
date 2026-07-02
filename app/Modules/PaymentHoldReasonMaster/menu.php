<?php

return [
    [
        'mode' => 'setup',
        'group' => 'Purchase',
        'label' => 'Payment Hold Reasons',
        'icon' => 'pause-circle',
        'route' => 'payment-hold-reason-master.index',
        'permission' => 'payment_hold_reason_master.view',
        'order' => 20,
    ],
];
