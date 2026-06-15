<?php

use App\Modules\RegistrationTypeMaster\Exporters\RegistrationTypeExporter;
use App\Modules\RegistrationTypeMaster\Importers\RegistrationTypeImporter;

return [
    'label' => 'Registration Types',
    'description' => 'Vehicle registration / plate types — private, commercial, government, BH series.',
    'group' => 'Vehicles',
    'icon' => 'identification',
    'permissions' => [
        'registration_type_master.view',
        'registration_type_master.create',
        'registration_type_master.update',
        'registration_type_master.delete',
        'registration_type_master.export',
        'registration_type_master.import',
    ],
    'exportable' => RegistrationTypeExporter::class,
    'importable' => RegistrationTypeImporter::class,
];
