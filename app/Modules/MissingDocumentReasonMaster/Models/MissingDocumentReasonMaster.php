<?php

namespace App\Modules\MissingDocumentReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\MissingDocumentReasonMaster\Database\Factories\MissingDocumentReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MissingDocumentReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'missing_document_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): MissingDocumentReasonMasterFactory
    {
        return MissingDocumentReasonMasterFactory::new();
    }
}
