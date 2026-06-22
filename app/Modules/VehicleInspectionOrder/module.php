<?php

use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;

return [
    'label' => 'Vehicle Inspection Orders',
    'description' => 'VIO/VIR — assign technician + bay, track time, capture item-wise results and before/after photos.',
    'group' => 'Inspection',
    'icon' => 'clipboard-document-check',
    'permissions' => [
        'vehicle_inspection_order.view',
        'vehicle_inspection_order.create',
        'vehicle_inspection_order.update',
        'vehicle_inspection_order.delete',
    ],
    'searchable' => [
        'model' => VehicleInspectionOrder::class,
        'route' => 'vehicle-inspection-order.index',
    ],
];
