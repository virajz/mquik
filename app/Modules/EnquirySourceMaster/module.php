<?php

use App\Modules\EnquirySourceMaster\Exporters\EnquirySourceExporter;
use App\Modules\EnquirySourceMaster\Importers\EnquirySourceImporter;

return [
    'label' => 'Enquiry Sources',
    'description' => 'Where the lead came from. Used by enquiry capture, lead scoring, and marketing reports.',
    'group' => 'CRM',
    'icon' => 'megaphone',
    'permissions' => [
        'enquiry_source_master.view',
        'enquiry_source_master.create',
        'enquiry_source_master.update',
        'enquiry_source_master.delete',
        'enquiry_source_master.export',
        'enquiry_source_master.import',
    ],
    'exportable' => EnquirySourceExporter::class,
    'importable' => EnquirySourceImporter::class,
];
