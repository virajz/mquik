<?php

use App\Modules\UnitOfMeasureMaster\Exporters\UnitOfMeasureExporter;
use App\Modules\UnitOfMeasureMaster\Importers\UnitOfMeasureImporter;

return [
    'label' => 'Units of Measure',
    'description' => 'Units used by spares, labour, consumables, and inventory.',
    'group' => 'Inventory',
    'icon' => 'scale',
    'permissions' => [
        'unit_of_measure_master.view',
        'unit_of_measure_master.create',
        'unit_of_measure_master.update',
        'unit_of_measure_master.delete',
        'unit_of_measure_master.export',
        'unit_of_measure_master.import',
    ],
    'exportable' => UnitOfMeasureExporter::class,
    'importable' => UnitOfMeasureImporter::class,
];
