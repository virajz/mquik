<?php

use App\Modules\HolidayMaster\Exporters\HolidayExporter;
use App\Modules\HolidayMaster\Importers\HolidayImporter;

return [
    'label' => 'Holidays',
    'description' => 'Workshop holiday calendar - weekly-off, national, festival & company holidays.',
    'group' => 'HR',
    'icon' => 'calendar-days',
    'permissions' => [
        'holiday_master.view',
        'holiday_master.create',
        'holiday_master.update',
        'holiday_master.delete',
        'holiday_master.export',
        'holiday_master.import',
    ],
    'exportable' => HolidayExporter::class,
    'importable' => HolidayImporter::class,
];
