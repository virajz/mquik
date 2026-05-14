<?php

use App\Modules\GateInOut\Models\GateInOut;

return [
    'label' => 'Gate In / Out',
    'description' => 'Inward and outward vehicle event log — ANPR camera or manual entry, reg-no standardised.',
    'group' => 'Workshop',
    'icon' => 'arrow-right-end-on-rectangle',
    'permissions' => [
        'gate_in_out.view',
        'gate_in_out.create',
        'gate_in_out.update',
        'gate_in_out.delete',
    ],
    'searchable' => [
        'model' => GateInOut::class,
        'route' => 'gate-in-out.index',
    ],
];
