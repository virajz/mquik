<?php

use App\Modules\JobDescriptionMaster\Exporters\JobDescriptionExporter;
use App\Modules\JobDescriptionMaster\Importers\JobDescriptionImporter;

return [
    'label' => 'Job Descriptions',
    'description' => 'Catalog of jobs the workshop performs — used by job cards and estimates.',
    'group' => 'Workshop',
    'icon' => 'list-bullet',
    'permissions' => [
        'job_description_master.view',
        'job_description_master.create',
        'job_description_master.update',
        'job_description_master.delete',
        'job_description_master.export',
        'job_description_master.import',
    ],
    'exportable' => JobDescriptionExporter::class,
    'importable' => JobDescriptionImporter::class,
];
