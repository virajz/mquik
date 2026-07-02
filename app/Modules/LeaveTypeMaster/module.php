<?php

use App\Modules\LeaveTypeMaster\Exporters\LeaveTypeExporter;
use App\Modules\LeaveTypeMaster\Importers\LeaveTypeImporter;

return [
    'label' => 'Leave Types',
    'description' => 'Types of employee leave - used by leave management.',
    'group' => 'HR',
    'icon' => 'calendar-days',
    'permissions' => [
        'leave_type_master.view',
        'leave_type_master.create',
        'leave_type_master.update',
        'leave_type_master.delete',
        'leave_type_master.export',
        'leave_type_master.import',
    ],
    'exportable' => LeaveTypeExporter::class,
    'importable' => LeaveTypeImporter::class,
];
