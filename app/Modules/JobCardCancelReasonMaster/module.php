<?php

use App\Modules\JobCardCancelReasonMaster\Exporters\JobCardCancelReasonExporter;
use App\Modules\JobCardCancelReasonMaster\Importers\JobCardCancelReasonImporter;

return [
    'label' => 'Job Card Cancel Reasons',
    'description' => 'Reasons that may be selected when cancelling an open job card.',
    'group' => 'Workshop',
    'icon' => 'x-circle',
    'permissions' => [
        'job_card_cancel_reason_master.view',
        'job_card_cancel_reason_master.create',
        'job_card_cancel_reason_master.update',
        'job_card_cancel_reason_master.delete',
        'job_card_cancel_reason_master.export',
        'job_card_cancel_reason_master.import',
    ],
    'exportable' => JobCardCancelReasonExporter::class,
    'importable' => JobCardCancelReasonImporter::class,
];
