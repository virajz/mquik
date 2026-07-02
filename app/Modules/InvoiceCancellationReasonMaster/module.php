<?php

use App\Modules\InvoiceCancellationReasonMaster\Exporters\InvoiceCancellationReasonExporter;
use App\Modules\InvoiceCancellationReasonMaster\Importers\InvoiceCancellationReasonImporter;

return [
    'label' => 'Invoice Cancellation Reasons',
    'description' => 'Reasons an invoice or credit note is cancelled — used by regular and counter sales invoices.',
    'group' => 'Sales',
    'icon' => 'x-circle',
    'permissions' => [
        'invoice_cancellation_reason_master.view',
        'invoice_cancellation_reason_master.create',
        'invoice_cancellation_reason_master.update',
        'invoice_cancellation_reason_master.delete',
        'invoice_cancellation_reason_master.export',
        'invoice_cancellation_reason_master.import',
    ],
    'exportable' => InvoiceCancellationReasonExporter::class,
    'importable' => InvoiceCancellationReasonImporter::class,
];
