<?php

use App\Modules\PaymentCancellationReasonMaster\Exporters\PaymentCancellationReasonExporter;
use App\Modules\PaymentCancellationReasonMaster\Importers\PaymentCancellationReasonImporter;

return [
    'label' => 'Payment Cancellation Reasons',
    'description' => 'Why a vendor payment is cancelled - used by regular payments.',
    'group' => 'Purchase',
    'icon' => 'x-circle',
    'permissions' => [
        'payment_cancellation_reason_master.view',
        'payment_cancellation_reason_master.create',
        'payment_cancellation_reason_master.update',
        'payment_cancellation_reason_master.delete',
        'payment_cancellation_reason_master.export',
        'payment_cancellation_reason_master.import',
    ],
    'exportable' => PaymentCancellationReasonExporter::class,
    'importable' => PaymentCancellationReasonImporter::class,
];
