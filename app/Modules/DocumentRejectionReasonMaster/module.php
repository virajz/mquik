<?php

use App\Modules\DocumentRejectionReasonMaster\Exporters\DocumentRejectionReasonExporter;
use App\Modules\DocumentRejectionReasonMaster\Importers\DocumentRejectionReasonImporter;

return [
    'label' => 'Document Rejection Reasons',
    'description' => 'Why a submitted document was rejected.',
    'group' => 'Insurance',
    'icon' => 'x-circle',
    'permissions' => [
        'document_rejection_reason_master.view',
        'document_rejection_reason_master.create',
        'document_rejection_reason_master.update',
        'document_rejection_reason_master.delete',
        'document_rejection_reason_master.export',
        'document_rejection_reason_master.import',
    ],
    'exportable' => DocumentRejectionReasonExporter::class,
    'importable' => DocumentRejectionReasonImporter::class,
];
