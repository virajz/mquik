<?php

use App\Modules\BookingChannelMaster\Exporters\BookingChannelExporter;
use App\Modules\BookingChannelMaster\Importers\BookingChannelImporter;

return [
    'label' => 'Booking Channels',
    'description' => 'How an appointment reached the workshop — walk-in, phone, website, app, WhatsApp, CRM or referral.',
    'group' => 'Workshop',
    'icon' => 'inbox-arrow-down',
    'permissions' => [
        'booking_channel_master.view',
        'booking_channel_master.create',
        'booking_channel_master.update',
        'booking_channel_master.delete',
        'booking_channel_master.export',
        'booking_channel_master.import',
    ],
    'exportable' => BookingChannelExporter::class,
    'importable' => BookingChannelImporter::class,
];
