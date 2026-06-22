<?php

use App\Modules\ChallanRejectionReasonMaster\Exporters\ChallanRejectionReasonExporter;
use App\Modules\ChallanRejectionReasonMaster\Importers\ChallanRejectionReasonImporter;

return [
    'label' => 'Challan Rejection Reasons',
    'description' => 'Why a challan line is rejected or an issue raised.',
    'group' => 'Purchase',
    'icon' => 'hand-raised',
    'permissions' => [
        'challan_rejection_reason_master.view',
        'challan_rejection_reason_master.create',
        'challan_rejection_reason_master.update',
        'challan_rejection_reason_master.delete',
        'challan_rejection_reason_master.export',
        'challan_rejection_reason_master.import',
    ],
    'exportable' => ChallanRejectionReasonExporter::class,
    'importable' => ChallanRejectionReasonImporter::class,
];
