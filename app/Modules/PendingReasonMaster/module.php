<?php

use App\Modules\PendingReasonMaster\Exporters\PendingReasonExporter;
use App\Modules\PendingReasonMaster\Importers\PendingReasonImporter;

return [
    'label' => 'Pending Reasons',
    'description' => 'Why an appointment or pickup/drop job is still pending, or was rescheduled — shared across modules.',
    'group' => 'Workshop',
    'icon' => 'pause-circle',
    'permissions' => [
        'pending_reason_master.view',
        'pending_reason_master.create',
        'pending_reason_master.update',
        'pending_reason_master.delete',
        'pending_reason_master.export',
        'pending_reason_master.import',
    ],
    'exportable' => PendingReasonExporter::class,
    'importable' => PendingReasonImporter::class,
];
