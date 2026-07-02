<?php

use App\Modules\ChequeBounceReasonMaster\Exporters\ChequeBounceReasonExporter;
use App\Modules\ChequeBounceReasonMaster\Importers\ChequeBounceReasonImporter;

return [
    'label' => 'Cheque Bounce Reasons',
    'description' => 'Why a cheque bounced - used by regular receipts to record cheque returns.',
    'group' => 'Sales',
    'icon' => 'exclamation-triangle',
    'permissions' => [
        'cheque_bounce_reason_master.view',
        'cheque_bounce_reason_master.create',
        'cheque_bounce_reason_master.update',
        'cheque_bounce_reason_master.delete',
        'cheque_bounce_reason_master.export',
        'cheque_bounce_reason_master.import',
    ],
    'exportable' => ChequeBounceReasonExporter::class,
    'importable' => ChequeBounceReasonImporter::class,
];
