<?php

namespace App\Modules\RegularSalesInvoice\Models;

use App\Modules\InsuranceDeductionTypeMaster\Models\InsuranceDeductionTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegularSalesInvoiceDeduction extends Model
{
    protected $table = 'regular_sales_invoice_deductions';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(RegularSalesInvoice::class, 'regular_sales_invoice_id');
    }

    public function deductionType(): BelongsTo
    {
        return $this->belongsTo(InsuranceDeductionTypeMaster::class, 'insurance_deduction_type_id');
    }
}
