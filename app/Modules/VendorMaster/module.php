<?php

use App\Modules\VendorMaster\Exporters\VendorExporter;
use App\Modules\VendorMaster\Importers\VendorImporter;
use App\Modules\VendorMaster\Models\VendorMaster;

return [
    'label' => 'Vendors',
    'description' => 'Suppliers of parts, labour, services, and insurance.',
    'group' => 'Vendors',
    'icon' => 'truck',
    'permissions' => [
        'vendor_master.view',
        'vendor_master.create',
        'vendor_master.update',
        'vendor_master.delete',
        'vendor_master.export',
        'vendor_master.import',
    ],
    'exportable' => VendorExporter::class,
    'importable' => VendorImporter::class,
    'searchable' => [
        'model' => VendorMaster::class,
        'route' => 'vendor-master.index',
    ],
];
