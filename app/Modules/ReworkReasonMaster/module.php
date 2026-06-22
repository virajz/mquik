<?php

use App\Modules\ReworkReasonMaster\Exporters\ReworkReasonExporter;
use App\Modules\ReworkReasonMaster\Importers\ReworkReasonImporter;

return [
    'label' => 'Rework Reasons',
    'description' => 'Reasons a job is sent back for rework — poor quality, missed task, complaint.',
    'group' => 'Workshop',
    'icon' => 'arrow-path',
    'permissions' => [
        'rework_reason_master.view',
        'rework_reason_master.create',
        'rework_reason_master.update',
        'rework_reason_master.delete',
        'rework_reason_master.export',
        'rework_reason_master.import',
    ],
    'exportable' => ReworkReasonExporter::class,
    'importable' => ReworkReasonImporter::class,
];
