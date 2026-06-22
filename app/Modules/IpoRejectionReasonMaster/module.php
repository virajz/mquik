<?php

use App\Modules\IpoRejectionReasonMaster\Exporters\IpoRejectionReasonExporter;
use App\Modules\IpoRejectionReasonMaster\Importers\IpoRejectionReasonImporter;

return [
    'label' => 'IPO Rejection Reasons',
    'description' => 'Why an internal part order approval was rejected.',
    'group' => 'Inventory',
    'icon' => 'hand-raised',
    'permissions' => [
        'ipo_rejection_reason_master.view',
        'ipo_rejection_reason_master.create',
        'ipo_rejection_reason_master.update',
        'ipo_rejection_reason_master.delete',
        'ipo_rejection_reason_master.export',
        'ipo_rejection_reason_master.import',
    ],
    'exportable' => IpoRejectionReasonExporter::class,
    'importable' => IpoRejectionReasonImporter::class,
];
