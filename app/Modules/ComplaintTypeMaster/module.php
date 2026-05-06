<?php

use App\Modules\ComplaintTypeMaster\Exporters\ComplaintTypeExporter;
use App\Modules\ComplaintTypeMaster\Importers\ComplaintTypeImporter;

return [
    'label' => 'Complaint Types',
    'description' => 'Categories of customer complaints — used by complaint logging, advisor routing, and CRM reports.',
    'group' => 'CRM',
    'icon' => 'exclamation-triangle',
    'permissions' => [
        'complaint_type_master.view',
        'complaint_type_master.create',
        'complaint_type_master.update',
        'complaint_type_master.delete',
        'complaint_type_master.export',
        'complaint_type_master.import',
    ],
    'exportable' => ComplaintTypeExporter::class,
    'importable' => ComplaintTypeImporter::class,
];
