<?php

use App\Modules\CompanyMaster\Models\CompanyMaster;

return [
    'label' => 'Company',
    'description' => 'Your workshop\'s legal entity — appears on invoices, letterheads, and reports.',
    'group' => 'Settings',
    'icon' => 'building-office',
    'permissions' => [
        'company_master.view',
        'company_master.update',
    ],
    'searchable' => [
        'model' => CompanyMaster::class,
        'route' => 'company-master.index',
    ],
];
