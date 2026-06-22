<?php

use App\Modules\WorkOrderHoldReasonMaster\Exporters\WorkOrderHoldReasonExporter;
use App\Modules\WorkOrderHoldReasonMaster\Importers\WorkOrderHoldReasonImporter;

return [
    'label' => 'Work Order Hold Reasons',
    'description' => 'Reasons a technician pauses or holds a work order — parts, approval, breaks.',
    'group' => 'Workshop',
    'icon' => 'pause-circle',
    'permissions' => [
        'work_order_hold_reason_master.view',
        'work_order_hold_reason_master.create',
        'work_order_hold_reason_master.update',
        'work_order_hold_reason_master.delete',
        'work_order_hold_reason_master.export',
        'work_order_hold_reason_master.import',
    ],
    'exportable' => WorkOrderHoldReasonExporter::class,
    'importable' => WorkOrderHoldReasonImporter::class,
];
