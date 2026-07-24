<?php

use App\Modules\HsnMaster\Exporters\HsnExporter;
use App\Modules\HsnMaster\Importers\HsnImporter;

return [
    'label' => 'HSN / SAC Codes',
    'description' => 'GST classification codes — HSN for goods, SAC for services, with the default rate.',
    'group' => 'Purchase',
    'icon' => 'hashtag',
    'permissions' => [
        'hsn_master.view',
        'hsn_master.create',
        'hsn_master.update',
        'hsn_master.delete',
        'hsn_master.export',
        'hsn_master.import',
    ],
    'exportable' => HsnExporter::class,
    'importable' => HsnImporter::class,
];
