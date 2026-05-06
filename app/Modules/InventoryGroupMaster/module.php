<?php

use App\Modules\InventoryGroupMaster\Exporters\InventoryGroupExporter;
use App\Modules\InventoryGroupMaster\Importers\InventoryGroupImporter;

return [
    'label' => 'Inventory Groups',
    'description' => 'Categories used by Spare and Labour catalogs — Brake, Suspension, Filters, etc. Sub-groups allowed.',
    'group' => 'Inventory',
    'icon' => 'square-3-stack-3d',
    'permissions' => [
        'inventory_group_master.view',
        'inventory_group_master.create',
        'inventory_group_master.update',
        'inventory_group_master.delete',
        'inventory_group_master.export',
        'inventory_group_master.import',
    ],
    'exportable' => InventoryGroupExporter::class,
    'importable' => InventoryGroupImporter::class,
];
