<?php

use App\Modules\ChecklistTemplateMaster\Exporters\ChecklistTemplateExporter;
use App\Modules\ChecklistTemplateMaster\Importers\ChecklistTemplateImporter;

return [
    'label' => 'Checklist Templates',
    'description' => 'Reusable checklists with inline items. Used by document collection, pre-delivery, safety, etc.',
    'group' => 'Workshop',
    'icon' => 'clipboard-document-check',
    'permissions' => [
        'checklist_template_master.view',
        'checklist_template_master.create',
        'checklist_template_master.update',
        'checklist_template_master.delete',
        'checklist_template_master.export',
        'checklist_template_master.import',
    ],
    'exportable' => ChecklistTemplateExporter::class,
    'importable' => ChecklistTemplateImporter::class,
];
