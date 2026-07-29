<?php

use App\Modules\CustomerComplaint\Models\CustomerComplaint;

return [
    'label' => 'Customer Complaints',
    'description' => 'Register and track resolution of customer / internal complaints — type, root cause, resolution and satisfaction.',
    'group' => 'CRM',
    'icon' => 'chat-bubble-left-ellipsis',
    'permissions' => [
        'customer_complaint.view',
        'customer_complaint.create',
        'customer_complaint.update',
        'customer_complaint.delete',
    ],
    'searchable' => [
        'model' => CustomerComplaint::class,
        'route' => 'customer-complaint.index',
    ],
];
