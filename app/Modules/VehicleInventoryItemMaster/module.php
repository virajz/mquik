<?php

use App\Modules\VehicleInventoryItemMaster\Exporters\VehicleInventoryItemExporter;
use App\Modules\VehicleInventoryItemMaster\Importers\VehicleInventoryItemImporter;

return [
    'label' => 'Vehicle Inventory Items',
    'description' => 'Items present with the vehicle at intake — ticked off by the advisor on the job card so nothing goes missing during service.',
    'group' => 'Workshop',
    'icon' => 'archive-box',
    'permissions' => [
        'vehicle_inventory_item_master.view',
        'vehicle_inventory_item_master.create',
        'vehicle_inventory_item_master.update',
        'vehicle_inventory_item_master.delete',
        'vehicle_inventory_item_master.export',
        'vehicle_inventory_item_master.import',
    ],
    'exportable' => VehicleInventoryItemExporter::class,
    'importable' => VehicleInventoryItemImporter::class,
];
