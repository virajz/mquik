<?php

use App\Modules\LossTypeMaster\Exporters\LossTypeExporter;
use App\Modules\LossTypeMaster\Importers\LossTypeImporter;

return [
    'label' => 'Loss Types',
    'description' => 'Loss classification for proforma (warranty vs damaged).',
    'group' => 'Sales',
    'icon' => 'shield-exclamation',
    'permissions' => [
        'loss_type_master.view',
        'loss_type_master.create',
        'loss_type_master.update',
        'loss_type_master.delete',
        'loss_type_master.export',
        'loss_type_master.import',
    ],
    'exportable' => LossTypeExporter::class,
    'importable' => LossTypeImporter::class,
];
