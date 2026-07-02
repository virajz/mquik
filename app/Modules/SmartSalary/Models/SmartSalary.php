<?php

namespace App\Modules\SmartSalary\Models;

use App\Concerns\Auditable;
use App\Modules\SmartSalary\Database\Factories\SmartSalaryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartSalary extends Model
{
    use Auditable;
    use HasFactory;

    public const DIRECTION_HIGHER = 'higher_is_better';

    public const DIRECTION_LOWER = 'lower_is_better';

    public const POLARITY_POSITIVE = 'positive';

    public const POLARITY_NEGATIVE = 'negative';

    protected $table = 'smart_salary_kpis';

    protected $guarded = [];

    protected $casts = [
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function newFactory(): SmartSalaryFactory
    {
        return SmartSalaryFactory::new();
    }

    /**
     * @return array<string, string>
     */
    public static function directions(): array
    {
        return [
            self::DIRECTION_HIGHER => 'Higher is better',
            self::DIRECTION_LOWER => 'Lower is better',
        ];
    }

    /**
     * Reward (adds points) vs penalty (deducts points) — drives the performance score.
     *
     * @return array<string, string>
     */
    public static function polarities(): array
    {
        return [
            self::POLARITY_POSITIVE => 'Positive (reward)',
            self::POLARITY_NEGATIVE => 'Negative (penalty)',
        ];
    }

    /**
     * Categories seeded for the UI dropdown — adding a new one here is enough.
     *
     * @return array<int, string>
     */
    public static function categories(): array
    {
        return [
            'Attendance',
            'Performance',
            'Sales',
            'Quality',
            'Discipline',
            'TAT',
            'Customer Feedback',
            'Process Compliance',
        ];
    }
}
