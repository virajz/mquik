<?php

use App\Modules\DesignationMaster\Exporters\DesignationExporter;
use App\Modules\DesignationMaster\Importers\DesignationImporter;

return [
    'label' => 'Designations',
    'description' => 'Job titles employees can be assigned to.',
    'group' => 'HR',
    'icon' => 'identification',
    'permissions' => [
        'designation_master.view',
        'designation_master.create',
        'designation_master.update',
        'designation_master.delete',
        'designation_master.export',
        'designation_master.import',
    ],
    'exportable' => DesignationExporter::class,
    'importable' => DesignationImporter::class,
];
