<?php

use App\Modules\CourierCompanyMaster\Exporters\CourierCompanyExporter;
use App\Modules\CourierCompanyMaster\Importers\CourierCompanyImporter;

return [
    'label' => 'Courier Companies',
    'description' => 'Couriers used for parts dispatch, document delivery, and vehicle drop-off.',
    'group' => 'Vendors',
    'icon' => 'truck',
    'permissions' => [
        'courier_company_master.view',
        'courier_company_master.create',
        'courier_company_master.update',
        'courier_company_master.delete',
        'courier_company_master.export',
        'courier_company_master.import',
    ],
    'exportable' => CourierCompanyExporter::class,
    'importable' => CourierCompanyImporter::class,
];
