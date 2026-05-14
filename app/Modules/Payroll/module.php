<?php

return [
    'label' => 'Payroll',
    'description' => 'Manual monthly payroll entry per employee.',
    'group' => 'HR',
    'icon' => 'banknotes',
    'permissions' => [
        'payroll.view',
        'payroll.create',
        'payroll.update',
        'payroll.delete',
    ],
];
