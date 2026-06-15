<?php

use App\Modules\TransmissionTypeMaster\Exporters\TransmissionTypeExporter;
use App\Modules\TransmissionTypeMaster\Importers\TransmissionTypeImporter;

return [
    'label' => 'Transmission Types',
    'description' => 'Vehicle transmission types — manual, automatic, AMT, CVT, DCT.',
    'group' => 'Vehicles',
    'icon' => 'cog-6-tooth',
    'permissions' => [
        'transmission_type_master.view',
        'transmission_type_master.create',
        'transmission_type_master.update',
        'transmission_type_master.delete',
        'transmission_type_master.export',
        'transmission_type_master.import',
    ],
    'exportable' => TransmissionTypeExporter::class,
    'importable' => TransmissionTypeImporter::class,
];
