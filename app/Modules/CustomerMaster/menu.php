<?php

return [
    [
        'group' => 'Masters',
        'label' => 'Customers',
        'icon' => 'user-circle',
        'route' => 'customer-master.index',
        'permission' => 'customer_master.view',
        'order' => 30,  // Customers come before Insurance + Spare Brands in the Masters group
    ],
];
