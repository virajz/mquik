<?php

use App\Modules\CustomerMaster\Exporters\CustomerExporter;
use App\Modules\CustomerMaster\Importers\CustomerImporter;

return [
    'label' => 'Customers',
    'description' => 'Customers who book services, buy parts, or own vehicles serviced here.',
    'group' => 'Masters',
    'icon' => 'user-circle',
    'permissions' => [
        'customer_master.view',
        'customer_master.create',
        'customer_master.update',
        'customer_master.delete',
        'customer_master.export',
        'customer_master.import',
    ],
    'exportable' => CustomerExporter::class,
    'importable' => CustomerImporter::class,
];
