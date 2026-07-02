<?php

use App\Modules\PerformanceSlabMaster\Exporters\PerformanceSlabExporter;
use App\Modules\PerformanceSlabMaster\Importers\PerformanceSlabImporter;

return [
    'label' => 'Performance Slabs',
    'description' => 'Productivity slabs mapping achievement % to an incentive amount.',
    'group' => 'HR',
    'icon' => 'chart-bar',
    'permissions' => [
        'performance_slab_master.view',
        'performance_slab_master.create',
        'performance_slab_master.update',
        'performance_slab_master.delete',
        'performance_slab_master.export',
        'performance_slab_master.import',
    ],
    'exportable' => PerformanceSlabExporter::class,
    'importable' => PerformanceSlabImporter::class,
];
