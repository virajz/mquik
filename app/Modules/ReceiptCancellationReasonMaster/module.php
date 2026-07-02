<?php

use App\Modules\ReceiptCancellationReasonMaster\Exporters\ReceiptCancellationReasonExporter;
use App\Modules\ReceiptCancellationReasonMaster\Importers\ReceiptCancellationReasonImporter;

return [
    'label' => 'Receipt Cancellation Reasons',
    'description' => 'Why a receipt is cancelled - used by regular receipts.',
    'group' => 'Sales',
    'icon' => 'x-circle',
    'permissions' => [
        'receipt_cancellation_reason_master.view',
        'receipt_cancellation_reason_master.create',
        'receipt_cancellation_reason_master.update',
        'receipt_cancellation_reason_master.delete',
        'receipt_cancellation_reason_master.export',
        'receipt_cancellation_reason_master.import',
    ],
    'exportable' => ReceiptCancellationReasonExporter::class,
    'importable' => ReceiptCancellationReasonImporter::class,
];
