<?php

use App\Modules\CancelReasonMaster\Exporters\CancelReasonExporter;
use App\Modules\CancelReasonMaster\Importers\CancelReasonImporter;

return [
    'label' => 'Cancel Reasons',
    'description' => 'Why an appointment or pickup/drop job was cancelled — shared by CRM reports and advisor follow-ups.',
    'group' => 'Workshop',
    'icon' => 'x-circle',
    'permissions' => [
        'cancel_reason_master.view',
        'cancel_reason_master.create',
        'cancel_reason_master.update',
        'cancel_reason_master.delete',
        'cancel_reason_master.export',
        'cancel_reason_master.import',
    ],
    'exportable' => CancelReasonExporter::class,
    'importable' => CancelReasonImporter::class,
];
