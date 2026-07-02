<?php

namespace App\Modules\RegularSalesInvoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegularSalesInvoiceAttachment extends Model
{
    protected $table = 'regular_sales_invoice_attachments';

    protected $guarded = [];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(RegularSalesInvoice::class, 'regular_sales_invoice_id');
    }
}
