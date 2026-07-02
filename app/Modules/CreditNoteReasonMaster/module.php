<?php

use App\Modules\CreditNoteReasonMaster\Exporters\CreditNoteReasonExporter;
use App\Modules\CreditNoteReasonMaster\Importers\CreditNoteReasonImporter;

return [
    'label' => 'Credit Note Reasons',
    'description' => 'Why a vendor credit or debit note is raised for returned goods.',
    'group' => 'Purchase',
    'icon' => 'receipt-refund',
    'permissions' => [
        'credit_note_reason_master.view',
        'credit_note_reason_master.create',
        'credit_note_reason_master.update',
        'credit_note_reason_master.delete',
        'credit_note_reason_master.export',
        'credit_note_reason_master.import',
    ],
    'exportable' => CreditNoteReasonExporter::class,
    'importable' => CreditNoteReasonImporter::class,
];
