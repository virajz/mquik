<?php

use App\Modules\MissingDocumentReasonMaster\Exporters\MissingDocumentReasonExporter;
use App\Modules\MissingDocumentReasonMaster\Importers\MissingDocumentReasonImporter;

return [
    'label' => 'Missing Document Reasons',
    'description' => 'Why a required document is missing during collection.',
    'group' => 'Insurance',
    'icon' => 'question-mark-circle',
    'permissions' => [
        'missing_document_reason_master.view',
        'missing_document_reason_master.create',
        'missing_document_reason_master.update',
        'missing_document_reason_master.delete',
        'missing_document_reason_master.export',
        'missing_document_reason_master.import',
    ],
    'exportable' => MissingDocumentReasonExporter::class,
    'importable' => MissingDocumentReasonImporter::class,
];
