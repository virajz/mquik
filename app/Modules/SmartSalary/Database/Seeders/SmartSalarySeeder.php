<?php

namespace App\Modules\SmartSalary\Database\Seeders;

use App\Modules\SmartSalary\Models\SmartSalary;
use Illuminate\Database\Seeder;

class SmartSalarySeeder extends Seeder
{
    public function run(): void
    {
        // Positive achievements (reward) and negative achievements (penalty) from the
        // Smart Salary requirement. `weight` is the max points the KPI contributes.
        $positive = [
            ['key' => 'JOB_CARD_FILLUP', 'name' => 'JOB CARD DATA FILLUP', 'category' => 'Process Compliance', 'weight' => 5],
            ['key' => 'TC_APPROVAL', 'name' => 'CUSTOMER T&C APPROVAL', 'category' => 'Process Compliance', 'weight' => 5],
            ['key' => 'DIGITAL_INSPECTION', 'name' => 'DIGITAL INSPECTION', 'category' => 'Quality', 'weight' => 5],
            ['key' => 'ESTIMATE_APPROVAL', 'name' => 'ESTIMATE APPROVAL', 'category' => 'Sales', 'weight' => 5],
            ['key' => 'BILLING_RATIO', 'name' => 'BILLING RATIO 70/30', 'category' => 'Performance', 'weight' => 10],
            ['key' => 'ONTIME_DELIVERY', 'name' => 'ONTIME DELIVERY', 'category' => 'TAT', 'weight' => 10],
            ['key' => 'TAT', 'name' => 'TURNAROUND TIME', 'category' => 'TAT', 'weight' => 10],
            ['key' => 'CUSTOMER_FEEDBACK', 'name' => 'CUSTOMER FEEDBACK / RATING', 'category' => 'Customer Feedback', 'weight' => 10],
            ['key' => 'ATTENDANCE_PUNCTUALITY', 'name' => 'ATTENDANCE & PUNCTUALITY', 'category' => 'Attendance', 'weight' => 10],
            ['key' => 'UNIFORM', 'name' => 'UNIFORM', 'category' => 'Discipline', 'weight' => 3],
            ['key' => 'CROSS_UPSALE', 'name' => 'CROSS SALES / UPSALE', 'category' => 'Sales', 'weight' => 10],
            ['key' => 'PROFORMA_APPROVAL', 'name' => 'PROFORMA APPROVAL', 'category' => 'Sales', 'weight' => 5],
            ['key' => 'ADVANCE_PAYMENT', 'name' => 'ADVANCE PAYMENT', 'category' => 'Sales', 'weight' => 5],
            ['key' => 'SERVICE_DUE_FOLLOWUP', 'name' => 'SERVICE DUE FOLLOWUPS', 'category' => 'Sales', 'weight' => 5],
            ['key' => 'RECOMMENDED_SERVICE_FOLLOWUP', 'name' => 'RECOMMENDED SERVICE FOLLOWUPS', 'category' => 'Sales', 'weight' => 5],
            ['key' => 'FINAL_INSPECTION', 'name' => 'FINAL INSPECTION', 'category' => 'Quality', 'weight' => 5],
            ['key' => 'DISCIPLINE', 'name' => 'DISCIPLINE / DEDICATION / COMMITMENT', 'category' => 'Discipline', 'weight' => 5],
            ['key' => 'LOYALTY', 'name' => 'LOYALTY', 'category' => 'Discipline', 'weight' => 5],
            ['key' => 'FEEDBACK', 'name' => 'FEEDBACK', 'category' => 'Customer Feedback', 'weight' => 5],
            ['key' => 'BACK_ORDER', 'name' => 'BACK ORDER HANDLING', 'category' => 'Process Compliance', 'weight' => 3],
        ];

        $negative = [
            ['key' => 'REPEAT_JOB', 'name' => 'REPEAT JOB / WARRANTY CLAIM', 'category' => 'Quality', 'weight' => 10],
            ['key' => 'LOSS_DAMAGE', 'name' => 'LOSS / DAMAGE', 'category' => 'Quality', 'weight' => 10],
            ['key' => 'JOB_CANCEL', 'name' => 'JOB CANCEL', 'category' => 'Process Compliance', 'weight' => 5],
            ['key' => 'SALES_RETURN', 'name' => 'SALES RETURN (RS/CS/IS)', 'category' => 'Sales', 'weight' => 5],
            ['key' => 'INVOICE_CORRECTION', 'name' => 'INVOICE CORRECTION', 'category' => 'Process Compliance', 'weight' => 5],
            ['key' => 'EXCESS_STOCK', 'name' => 'EXCESS STOCK', 'category' => 'Process Compliance', 'weight' => 5],
            ['key' => 'UNNECESSARY_LEAVE', 'name' => 'UNNECESSARY LEAVE', 'category' => 'Attendance', 'weight' => 5],
        ];

        $order = 0;
        foreach ([SmartSalary::POLARITY_POSITIVE => $positive, SmartSalary::POLARITY_NEGATIVE => $negative] as $polarity => $rows) {
            $direction = $polarity === SmartSalary::POLARITY_POSITIVE ? SmartSalary::DIRECTION_HIGHER : SmartSalary::DIRECTION_LOWER;
            foreach ($rows as $row) {
                SmartSalary::firstOrCreate(
                    ['key' => $row['key']],
                    [
                        'name' => $row['name'],
                        'category' => $row['category'],
                        'polarity' => $polarity,
                        'direction' => $direction,
                        'unit' => 'points',
                        'weight' => $row['weight'],
                        'is_active' => true,
                        'sort_order' => ++$order,
                    ],
                );
            }
        }
    }
}
