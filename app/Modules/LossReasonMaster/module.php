<?php

use App\Modules\LossReasonMaster\Exporters\LossReasonExporter;
use App\Modules\LossReasonMaster\Importers\LossReasonImporter;

return [
    'label' => 'Loss Reasons',
    'description' => 'Why outside-labour parts are recorded as a loss.',
    'group' => 'Purchase',
    'icon' => 'exclamation-triangle',
    'permissions' => [
        'loss_reason_master.view',
        'loss_reason_master.create',
        'loss_reason_master.update',
        'loss_reason_master.delete',
        'loss_reason_master.export',
        'loss_reason_master.import',
    ],
    'exportable' => LossReasonExporter::class,
    'importable' => LossReasonImporter::class,
];
