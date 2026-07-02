<?php

use App\Modules\ReceiptDifferenceReasonMaster\Exporters\ReceiptDifferenceReasonExporter;
use App\Modules\ReceiptDifferenceReasonMaster\Importers\ReceiptDifferenceReasonImporter;

return [
    'label' => 'Receipt Difference Reasons',
    'description' => 'Why the received amount differs from the expected - used by regular receipts.',
    'group' => 'Sales',
    'icon' => 'scale',
    'permissions' => [
        'receipt_difference_reason_master.view',
        'receipt_difference_reason_master.create',
        'receipt_difference_reason_master.update',
        'receipt_difference_reason_master.delete',
        'receipt_difference_reason_master.export',
        'receipt_difference_reason_master.import',
    ],
    'exportable' => ReceiptDifferenceReasonExporter::class,
    'importable' => ReceiptDifferenceReasonImporter::class,
];
