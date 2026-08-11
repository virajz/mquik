<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The "where else does this record appear?" map behind the record panel.
 *
 * Given a customer, vehicle or job card, returns the areas of the app that hold
 * related work — each with a live count and a deep link that lands on the target
 * index already filtered to this record.
 *
 * Targets are curated rather than derived from foreign keys: dozens of tables
 * carry a `customer_id`, but only a handful are places a user actually wants to
 * jump to. Each target's index must honour the matching URL filter (see
 * `App\Concerns\ScopesToRecord`).
 */
class RelatedRecords
{
    public const SUBJECT_CUSTOMER = 'customer';

    public const SUBJECT_VEHICLE = 'vehicle';

    public const SUBJECT_JOB_CARD = 'job_card';

    /**
     * Curated targets per subject, in the order they should appear.
     *
     * table  — where to count rows
     * column — the FK on that table pointing back at the subject
     * route  — target index route name
     * param  — query-string key that index reads (see ScopesToRecord)
     *
     * @return array<string, list<array{label: string, icon: string, table: string, column: string, route: string, param: string}>>
     */
    public static function map(): array
    {
        $customerParam = 'customer';
        $vehicleParam = 'vehicle';
        $jobCardParam = 'job_card';

        return [
            self::SUBJECT_CUSTOMER => [
                ['label' => 'Vehicles', 'icon' => 'truck', 'table' => 'customer_vehicles', 'column' => 'customer_id', 'route' => 'customer-vehicle-master.index', 'param' => $customerParam],
                ['label' => 'Job Cards', 'icon' => 'clipboard-document-list', 'table' => 'job_cards', 'column' => 'customer_id', 'route' => 'job-card.index', 'param' => $customerParam],
                ['label' => 'Appointments', 'icon' => 'calendar-days', 'table' => 'appointments', 'column' => 'customer_id', 'route' => 'appointment.index', 'param' => $customerParam],
                ['label' => 'Estimates', 'icon' => 'calculator', 'table' => 'sales_estimates', 'column' => 'customer_id', 'route' => 'sales-estimate.index', 'param' => $customerParam],
                ['label' => 'Invoices', 'icon' => 'document-currency-rupee', 'table' => 'regular_sales_invoices', 'column' => 'customer_id', 'route' => 'regular-sales-invoice.index', 'param' => $customerParam],
                ['label' => 'Receipts', 'icon' => 'banknotes', 'table' => 'regular_receipts', 'column' => 'customer_id', 'route' => 'regular-receipt.index', 'param' => $customerParam],
                ['label' => 'Documents', 'icon' => 'folder-open', 'table' => 'document_collections', 'column' => 'customer_id', 'route' => 'document-collection.index', 'param' => $customerParam],
                ['label' => 'Complaints', 'icon' => 'exclamation-triangle', 'table' => 'customer_complaints', 'column' => 'customer_id', 'route' => 'customer-complaint.index', 'param' => $customerParam],
                ['label' => 'Pickup / Drop', 'icon' => 'map-pin', 'table' => 'pickup_drops', 'column' => 'customer_id', 'route' => 'pickup-drop.index', 'param' => $customerParam],
            ],

            self::SUBJECT_VEHICLE => [
                ['label' => 'Job Cards', 'icon' => 'clipboard-document-list', 'table' => 'job_cards', 'column' => 'customer_vehicle_id', 'route' => 'job-card.index', 'param' => $vehicleParam],
                ['label' => 'Appointments', 'icon' => 'calendar-days', 'table' => 'appointments', 'column' => 'customer_vehicle_id', 'route' => 'appointment.index', 'param' => $vehicleParam],
                // Inspections hang off the job card, not the vehicle — reach them
                // through a job card rather than listing a dead link here.
                ['label' => 'Estimates', 'icon' => 'calculator', 'table' => 'sales_estimates', 'column' => 'customer_vehicle_id', 'route' => 'sales-estimate.index', 'param' => $vehicleParam],
                ['label' => 'Invoices', 'icon' => 'document-currency-rupee', 'table' => 'regular_sales_invoices', 'column' => 'customer_vehicle_id', 'route' => 'regular-sales-invoice.index', 'param' => $vehicleParam],
                ['label' => 'Pickup / Drop', 'icon' => 'map-pin', 'table' => 'pickup_drops', 'column' => 'customer_vehicle_id', 'route' => 'pickup-drop.index', 'param' => $vehicleParam],
            ],

            self::SUBJECT_JOB_CARD => [
                ['label' => 'Work Orders', 'icon' => 'clipboard-document-check', 'table' => 'vehicle_inspection_orders', 'column' => 'job_card_id', 'route' => 'vehicle-inspection-order.index', 'param' => $jobCardParam],
                ['label' => 'Inspections', 'icon' => 'magnifying-glass-circle', 'table' => 'digital_inspections', 'column' => 'job_card_id', 'route' => 'digital-inspection.index', 'param' => $jobCardParam],
                ['label' => 'Parts Inquiries', 'icon' => 'cube', 'table' => 'internal_parts_inquiries', 'column' => 'job_card_id', 'route' => 'internal-parts-inquiry.index', 'param' => $jobCardParam],
                ['label' => 'Estimates', 'icon' => 'calculator', 'table' => 'sales_estimates', 'column' => 'job_card_id', 'route' => 'sales-estimate.index', 'param' => $jobCardParam],
                ['label' => 'Invoices', 'icon' => 'document-currency-rupee', 'table' => 'regular_sales_invoices', 'column' => 'job_card_id', 'route' => 'regular-sales-invoice.index', 'param' => $jobCardParam],
                ['label' => 'Documents', 'icon' => 'folder-open', 'table' => 'document_collections', 'column' => 'job_card_id', 'route' => 'document-collection.index', 'param' => $jobCardParam],
                ['label' => 'Pickup / Drop', 'icon' => 'map-pin', 'table' => 'pickup_drops', 'column' => 'job_card_id', 'route' => 'pickup-drop.index', 'param' => $jobCardParam],
            ],
        ];
    }

    /**
     * Resolve every target for a subject, with counts and ready-made URLs.
     *
     * Targets whose table or column no longer exists are skipped rather than
     * fataling — modules get reshaped, and a stale entry should not break a
     * customer page.
     *
     * @return list<array{label: string, icon: string, count: int, url: string}>
     */
    public static function for(string $subject, int $recordId): array
    {
        $targets = self::map()[$subject] ?? [];
        $out = [];

        foreach ($targets as $t) {
            if (! self::tableHasColumn($t['table'], $t['column'])) {
                continue;
            }

            $out[] = [
                'label' => $t['label'],
                'icon' => $t['icon'],
                'count' => DB::table($t['table'])->where($t['column'], $recordId)->count(),
                'url' => route($t['route'], [$t['param'] => $recordId]),
            ];
        }

        return $out;
    }

    /** Cached per request — the panel asks about the same tables repeatedly. */
    protected static function tableHasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table.'.'.$column;

        return $cache[$key] ??= Schema::hasColumn($table, $column);
    }
}
