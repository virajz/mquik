<?php

use App\Modules\ChallanReasonMaster\Exporters\ChallanReasonExporter;
use App\Modules\ChallanReasonMaster\Importers\ChallanReasonImporter;

return [
    'label' => 'Challan Reasons',
    'description' => 'Why a challan is raised (Warranty, Damage and Adjustment).',
    'group' => 'Purchase',
    'icon' => 'document-text',
    'permissions' => [
        'challan_reason_master.view',
        'challan_reason_master.create',
        'challan_reason_master.update',
        'challan_reason_master.delete',
        'challan_reason_master.export',
        'challan_reason_master.import',
    ],
    'exportable' => ChallanReasonExporter::class,
    'importable' => ChallanReasonImporter::class,
];
