<?php

use App\Modules\IpiRejectionReasonMaster\Exporters\IpiRejectionReasonExporter;
use App\Modules\IpiRejectionReasonMaster\Importers\IpiRejectionReasonImporter;

return [
    'label' => 'IPI Rejection Reasons',
    'description' => 'Why the store rejects an internal parts inquiry — not in stock, obsolete, wrong part, budget issue.',
    'group' => 'Inventory',
    'icon' => 'hand-raised',
    'permissions' => [
        'ipi_rejection_reason_master.view',
        'ipi_rejection_reason_master.create',
        'ipi_rejection_reason_master.update',
        'ipi_rejection_reason_master.delete',
        'ipi_rejection_reason_master.export',
        'ipi_rejection_reason_master.import',
    ],
    'exportable' => IpiRejectionReasonExporter::class,
    'importable' => IpiRejectionReasonImporter::class,
];
