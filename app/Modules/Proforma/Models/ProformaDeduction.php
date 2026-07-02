<?php

namespace App\Modules\Proforma\Models;

use App\Modules\InsuranceDeductionTypeMaster\Models\InsuranceDeductionTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaDeduction extends Model
{
    protected $table = 'proforma_deductions';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class, 'proforma_id');
    }

    public function deductionType(): BelongsTo
    {
        return $this->belongsTo(InsuranceDeductionTypeMaster::class, 'insurance_deduction_type_id');
    }
}
