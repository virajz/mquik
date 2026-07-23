<?php

use App\Modules\ItemRejectionReasonMaster\Exporters\ItemRejectionReasonExporter;
use App\Modules\ItemRejectionReasonMaster\Importers\ItemRejectionReasonImporter;

return [
    'label' => 'Item Rejection Reasons',
    'description' => 'Why a challan line is rejected or an issue raised.',
    'group' => 'Purchase',
    'icon' => 'hand-raised',
    'permissions' => [
        'item_rejection_reason_master.view',
        'item_rejection_reason_master.create',
        'item_rejection_reason_master.update',
        'item_rejection_reason_master.delete',
        'item_rejection_reason_master.export',
        'item_rejection_reason_master.import',
    ],
    'exportable' => ItemRejectionReasonExporter::class,
    'importable' => ItemRejectionReasonImporter::class,
];
