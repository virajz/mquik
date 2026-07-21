<?php

use App\Modules\GateInOut\Models\GateInOut;

return [
    'label' => 'Inward / Outward',
    'description' => 'Vehicle entry and exit as one visit record — gate, parking slot, outward type and TAT.',
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
