<?php

use App\Modules\VendorTypeMaster\Exporters\VendorTypeExporter;
use App\Modules\VendorTypeMaster\Importers\VendorTypeImporter;

return [
    'label' => 'Vendor Types',
    'description' => 'Categories of vendors — Spare Parts, OSL, OEM, Service, Insurance.',
    'group' => 'Vendors',
    'icon' => 'tag',
    'permissions' => [
        'vendor_type_master.view',
        'vendor_type_master.create',
        'vendor_type_master.update',
        'vendor_type_master.delete',
        'vendor_type_master.export',
        'vendor_type_master.import',
    ],
    'exportable' => VendorTypeExporter::class,
    'importable' => VendorTypeImporter::class,
];
