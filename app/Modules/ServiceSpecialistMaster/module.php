<?php

use App\Modules\ServiceSpecialistMaster\Exporters\ServiceSpecialistExporter;
use App\Modules\ServiceSpecialistMaster\Importers\ServiceSpecialistImporter;

return [
    'label' => 'Service Specialists',
    'description' => 'Vendor service specialities (Denting, Painting, AC, Engine...).',
    'group' => 'Vendors',
    'icon' => 'wrench',
    'permissions' => [
        'service_specialist_master.view',
        'service_specialist_master.create',
        'service_specialist_master.update',
        'service_specialist_master.delete',
        'service_specialist_master.export',
        'service_specialist_master.import',
    ],
    'exportable' => ServiceSpecialistExporter::class,
    'importable' => ServiceSpecialistImporter::class,
];
