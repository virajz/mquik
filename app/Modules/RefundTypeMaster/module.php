<?php

use App\Modules\RefundTypeMaster\Exporters\RefundTypeExporter;
use App\Modules\RefundTypeMaster\Importers\RefundTypeImporter;

return [
    'label' => 'Refund Types',
    'description' => 'Types of customer refund - used by receipt refund requests.',
    'group' => 'Sales',
    'icon' => 'receipt-refund',
    'permissions' => [
        'refund_type_master.view',
        'refund_type_master.create',
        'refund_type_master.update',
        'refund_type_master.delete',
        'refund_type_master.export',
        'refund_type_master.import',
    ],
    'exportable' => RefundTypeExporter::class,
    'importable' => RefundTypeImporter::class,
];
