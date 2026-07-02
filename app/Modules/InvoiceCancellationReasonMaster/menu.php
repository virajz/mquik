<?php

return [
    [
        'mode' => 'setup',
        'group' => 'Sales',
        'label' => 'Invoice Cancellation Reasons',
        'icon' => 'x-circle',
        'route' => 'invoice-cancellation-reason-master.index',
        'permission' => 'invoice_cancellation_reason_master.view',
        'order' => 20,
    ],
];
