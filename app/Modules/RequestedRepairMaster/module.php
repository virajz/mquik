<?php

use App\Modules\RequestedRepairMaster\Exporters\RequestedRepairExporter;
use App\Modules\RequestedRepairMaster\Importers\RequestedRepairImporter;

return [
    'label' => 'Requested Repairs',
    'description' => 'Common miscellaneous repairs a customer can request on a job card.',
    'group' => 'Workshop',
    'icon' => 'wrench-screwdriver',
    'permissions' => [
        'requested_repair_master.view',
        'requested_repair_master.create',
        'requested_repair_master.update',
        'requested_repair_master.delete',
        'requested_repair_master.export',
        'requested_repair_master.import',
    ],
    'exportable' => RequestedRepairExporter::class,
    'importable' => RequestedRepairImporter::class,
];
