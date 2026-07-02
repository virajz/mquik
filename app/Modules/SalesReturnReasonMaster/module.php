<?php

use App\Modules\SalesReturnReasonMaster\Exporters\SalesReturnReasonExporter;
use App\Modules\SalesReturnReasonMaster\Importers\SalesReturnReasonImporter;

return [
    'label' => 'Sales Return Reasons',
    'description' => 'Why goods are returned — used by regular / insurance / counter sales returns.',
    'group' => 'Sales',
    'icon' => 'arrow-uturn-left',
    'permissions' => [
        'sales_return_reason_master.view',
        'sales_return_reason_master.create',
        'sales_return_reason_master.update',
        'sales_return_reason_master.delete',
        'sales_return_reason_master.export',
        'sales_return_reason_master.import',
    ],
    'exportable' => SalesReturnReasonExporter::class,
    'importable' => SalesReturnReasonImporter::class,
];
