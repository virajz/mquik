<?php

use App\Modules\IpoCancellationReasonMaster\Exporters\IpoCancellationReasonExporter;
use App\Modules\IpoCancellationReasonMaster\Importers\IpoCancellationReasonImporter;

return [
    'label' => 'IPO Cancellation Reasons',
    'description' => 'Why an internal part order was cancelled.',
    'group' => 'Inventory',
    'icon' => 'x-circle',
    'permissions' => [
        'ipo_cancellation_reason_master.view',
        'ipo_cancellation_reason_master.create',
        'ipo_cancellation_reason_master.update',
        'ipo_cancellation_reason_master.delete',
        'ipo_cancellation_reason_master.export',
        'ipo_cancellation_reason_master.import',
    ],
    'exportable' => IpoCancellationReasonExporter::class,
    'importable' => IpoCancellationReasonImporter::class,
];
