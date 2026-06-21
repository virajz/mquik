<?php

namespace App\Modules\DocumentRejectionReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\DocumentRejectionReasonMaster\Database\Factories\DocumentRejectionReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRejectionReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'document_rejection_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): DocumentRejectionReasonMasterFactory
    {
        return DocumentRejectionReasonMasterFactory::new();
    }
}
