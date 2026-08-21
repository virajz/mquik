<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOtp extends Model
{
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public const PURPOSE_PHONE_VERIFICATION = 'phone_verification';

    /** A code is useless after this many wrong guesses. */
    public const MAX_ATTEMPTS = 5;

    protected $table = 'user_otps';

    protected $guarded = [];

    protected $hidden = ['code_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function isUsable(): bool
    {
        return $this->consumed_at === null
            && $this->attempts < self::MAX_ATTEMPTS
            && $this->expires_at->isFuture();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
