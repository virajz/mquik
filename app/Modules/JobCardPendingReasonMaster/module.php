<?php

use App\Modules\JobCardPendingReasonMaster\Exporters\JobCardPendingReasonExporter;
use App\Modules\JobCardPendingReasonMaster\Importers\JobCardPendingReasonImporter;

return [
    'label' => 'Job Card Pending Reasons',
    'description' => 'Reasons that may be selected when a job card is paused or held mid-flow.',
    'group' => 'Workshop',
    'icon' => 'pause-circle',
    'permissions' => [
        'job_card_pending_reason_master.view',
        'job_card_pending_reason_master.create',
        'job_card_pending_reason_master.update',
        'job_card_pending_reason_master.delete',
        'job_card_pending_reason_master.export',
        'job_card_pending_reason_master.import',
    ],
    'exportable' => JobCardPendingReasonExporter::class,
    'importable' => JobCardPendingReasonImporter::class,
];
