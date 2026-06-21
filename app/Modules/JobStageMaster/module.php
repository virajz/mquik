<?php

use App\Modules\JobStageMaster\Exporters\JobStageExporter;
use App\Modules\JobStageMaster\Importers\JobStageImporter;

return [
    'label' => 'Job Stages',
    'description' => 'Job lifecycle stages (regular vs insurance track) — drive the Job Card status tree and history.',
    'group' => 'Workshop',
    'icon' => 'list-bullet',
    'permissions' => [
        'job_stage_master.view',
        'job_stage_master.create',
        'job_stage_master.update',
        'job_stage_master.delete',
        'job_stage_master.export',
        'job_stage_master.import',
    ],
    'exportable' => JobStageExporter::class,
    'importable' => JobStageImporter::class,
];
