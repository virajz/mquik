<?php

use App\Modules\GstTypeMaster\Exporters\GstTypeExporter;
use App\Modules\GstTypeMaster\Importers\GstTypeImporter;

return [
    'label' => 'GST Types',
    'description' => 'Customer/vendor GST registration type — used for tax calculation.',
    'group' => 'Finance',
    'icon' => 'receipt-percent',
    'permissions' => [
        'gst_type_master.view',
        'gst_type_master.create',
        'gst_type_master.update',
        'gst_type_master.delete',
        'gst_type_master.export',
        'gst_type_master.import',
    ],
    'exportable' => GstTypeExporter::class,
    'importable' => GstTypeImporter::class,
];
