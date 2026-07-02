<?php

namespace App\Modules\CreditNoteReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CreditNoteReasonMaster\Database\Factories\CreditNoteReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNoteReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'credit_note_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): CreditNoteReasonMasterFactory
    {
        return CreditNoteReasonMasterFactory::new();
    }
}
