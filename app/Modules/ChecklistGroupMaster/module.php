<?php

use App\Modules\ChecklistGroupMaster\Exporters\ChecklistGroupExporter;
use App\Modules\ChecklistGroupMaster\Importers\ChecklistGroupImporter;

return [
    'label' => 'Checklist Groups',
    'description' => 'Categories of generic checklists — Document Collection, Pre-Delivery, Safety, etc. (Distinct from Vehicle Inspection.)',
    'group' => 'Workshop',
    'icon' => 'clipboard-document-list',
    'permissions' => [
        'checklist_group_master.view',
        'checklist_group_master.create',
        'checklist_group_master.update',
        'checklist_group_master.delete',
        'checklist_group_master.export',
        'checklist_group_master.import',
    ],
    'exportable' => ChecklistGroupExporter::class,
    'importable' => ChecklistGroupImporter::class,
];
