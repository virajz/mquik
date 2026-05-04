<?php

return [
    'label' => 'Insurance Companies',
    'description' => 'Insurers this workshop deals with for claims and policy renewals.',
    'group' => 'Masters',
    'icon' => 'shield-check',
    'permissions' => [
        'insurance_company_master.view',
        'insurance_company_master.create',
        'insurance_company_master.update',
        'insurance_company_master.delete',
    ],
];
