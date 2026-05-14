<?php

namespace App\Modules\Payroll\Models;

use App\Concerns\Auditable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\Payroll\Database\Factories\PayrollFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use Auditable;
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    public const STATUS_PAID = 'paid';

    protected $table = 'payrolls';

    protected $guarded = [];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'basic_amount' => 'decimal:2',
        'hra_amount' => 'decimal:2',
        'da_amount' => 'decimal:2',
        'allowances_amount' => 'decimal:2',
        'deductions_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function newFactory(): PayrollFactory
    {
        return PayrollFactory::new();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_FINALIZED => 'Finalized',
            self::STATUS_PAID => 'Paid',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function months(): array
    {
        return [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }

    public function periodLabel(): string
    {
        return (self::months()[$this->period_month] ?? '?').' '.$this->period_year;
    }
}
