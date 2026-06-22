<?php

use App\Modules\DelayReasonMaster\Exporters\DelayReasonExporter;
use App\Modules\DelayReasonMaster\Importers\DelayReasonImporter;

return [
    'label' => 'Delay Reasons',
    'description' => 'Reasons a work order is delayed — tools, power, extra diagnosis.',
    'group' => 'Workshop',
    'icon' => 'clock',
    'permissions' => [
        'delay_reason_master.view',
        'delay_reason_master.create',
        'delay_reason_master.update',
        'delay_reason_master.delete',
        'delay_reason_master.export',
        'delay_reason_master.import',
    ],
    'exportable' => DelayReasonExporter::class,
    'importable' => DelayReasonImporter::class,
];
