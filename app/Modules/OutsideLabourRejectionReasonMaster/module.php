<?php

use App\Modules\OutsideLabourRejectionReasonMaster\Exporters\OutsideLabourRejectionReasonExporter;
use App\Modules\OutsideLabourRejectionReasonMaster\Importers\OutsideLabourRejectionReasonImporter;

return [
    'label' => 'OSL Rejection Reasons',
    'description' => 'Why a contractor/vendor rejects an outside-labour inquiry — high cost, delay, poor quality, unavailable.',
    'group' => 'Workshop',
    'icon' => 'hand-raised',
    'permissions' => [
        'outside_labour_rejection_reason_master.view',
        'outside_labour_rejection_reason_master.create',
        'outside_labour_rejection_reason_master.update',
        'outside_labour_rejection_reason_master.delete',
        'outside_labour_rejection_reason_master.export',
        'outside_labour_rejection_reason_master.import',
    ],
    'exportable' => OutsideLabourRejectionReasonExporter::class,
    'importable' => OutsideLabourRejectionReasonImporter::class,
];
