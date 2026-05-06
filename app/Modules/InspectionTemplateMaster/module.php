<?php

use App\Modules\InspectionTemplateMaster\Exporters\InspectionTemplateExporter;
use App\Modules\InspectionTemplateMaster\Importers\InspectionTemplateImporter;

return [
    'label' => 'Inspection Templates',
    'description' => 'Reusable inspection checklists for PMS, Tyre, Bodyshop, Basic, and Custom inspections.',
    'group' => 'Inspection',
    'icon' => 'clipboard-document-list',
    'permissions' => [
        'inspection_template_master.view',
        'inspection_template_master.create',
        'inspection_template_master.update',
        'inspection_template_master.delete',
        'inspection_template_master.export',
        'inspection_template_master.import',
    ],
    'exportable' => InspectionTemplateExporter::class,
    'importable' => InspectionTemplateImporter::class,
];
