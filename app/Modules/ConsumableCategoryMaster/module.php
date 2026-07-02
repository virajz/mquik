<?php

use App\Modules\ConsumableCategoryMaster\Exporters\ConsumableCategoryExporter;
use App\Modules\ConsumableCategoryMaster\Importers\ConsumableCategoryImporter;

return [
    'label' => 'Consumable Categories',
    'description' => 'Consumable categories (Paint Shop, Workshop, Washing, Detailing, General).',
    'group' => 'Inventory',
    'icon' => 'beaker',
    'permissions' => [
        'consumable_category_master.view',
        'consumable_category_master.create',
        'consumable_category_master.update',
        'consumable_category_master.delete',
        'consumable_category_master.export',
        'consumable_category_master.import',
    ],
    'exportable' => ConsumableCategoryExporter::class,
    'importable' => ConsumableCategoryImporter::class,
];
