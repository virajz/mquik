<?php

use App\Modules\PaymentModeMaster\Exporters\PaymentModeExporter;
use App\Modules\PaymentModeMaster\Importers\PaymentModeImporter;

return [
    'label' => 'Payment Modes',
    'description' => 'Payment methods accepted at the cashier and used in invoices.',
    'group' => 'Finance',
    'icon' => 'banknotes',
    'permissions' => [
        'payment_mode_master.view',
        'payment_mode_master.create',
        'payment_mode_master.update',
        'payment_mode_master.delete',
        'payment_mode_master.export',
        'payment_mode_master.import',
    ],
    'exportable' => PaymentModeExporter::class,
    'importable' => PaymentModeImporter::class,
];
