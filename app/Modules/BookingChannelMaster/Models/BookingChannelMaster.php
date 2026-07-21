<?php

namespace App\Modules\BookingChannelMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\BookingChannelMaster\Database\Factories\BookingChannelMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingChannelMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'booking_channels';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): BookingChannelMasterFactory
    {
        return BookingChannelMasterFactory::new();
    }
}
