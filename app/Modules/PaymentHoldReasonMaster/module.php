<?php

use App\Modules\PaymentHoldReasonMaster\Exporters\PaymentHoldReasonExporter;
use App\Modules\PaymentHoldReasonMaster\Importers\PaymentHoldReasonImporter;

return [
    'label' => 'Payment Hold Reasons',
    'description' => 'Why a vendor payment is put on hold - used by regular payments.',
    'group' => 'Purchase',
    'icon' => 'pause-circle',
    'permissions' => [
        'payment_hold_reason_master.view',
        'payment_hold_reason_master.create',
        'payment_hold_reason_master.update',
        'payment_hold_reason_master.delete',
        'payment_hold_reason_master.export',
        'payment_hold_reason_master.import',
    ],
    'exportable' => PaymentHoldReasonExporter::class,
    'importable' => PaymentHoldReasonImporter::class,
];
