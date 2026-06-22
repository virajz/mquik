<?php

use App\Modules\TransportModeMaster\Exporters\TransportModeExporter;
use App\Modules\TransportModeMaster\Importers\TransportModeImporter;

return [
    'label' => 'Transport Modes',
    'description' => 'How material is moved (Self, Courier, Porter, Liftor...).',
    'group' => 'Purchase',
    'icon' => 'truck',
    'permissions' => [
        'transport_mode_master.view',
        'transport_mode_master.create',
        'transport_mode_master.update',
        'transport_mode_master.delete',
        'transport_mode_master.export',
        'transport_mode_master.import',
    ],
    'exportable' => TransportModeExporter::class,
    'importable' => TransportModeImporter::class,
];
