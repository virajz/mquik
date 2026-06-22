<?php

use App\Modules\EstimateRevisionReasonMaster\Exporters\EstimateRevisionReasonExporter;
use App\Modules\EstimateRevisionReasonMaster\Importers\EstimateRevisionReasonImporter;

return [
    'label' => 'Estimate Revision Reasons',
    'description' => 'Why a sales estimate was revised.',
    'group' => 'Sales',
    'icon' => 'arrow-path',
    'permissions' => [
        'estimate_revision_reason_master.view',
        'estimate_revision_reason_master.create',
        'estimate_revision_reason_master.update',
        'estimate_revision_reason_master.delete',
        'estimate_revision_reason_master.export',
        'estimate_revision_reason_master.import',
    ],
    'exportable' => EstimateRevisionReasonExporter::class,
    'importable' => EstimateRevisionReasonImporter::class,
];
