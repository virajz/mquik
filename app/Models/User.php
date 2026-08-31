<?php

namespace App\Models;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Support\FinancialYear;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'phone', 'password', 'must_reset_password', 'user_type', 'employee_id', 'vendor_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'must_reset_password' => 'boolean',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // FY-aware series, same shape as the invoice numbering: MQ/US/26-27/0001.
        static::created(function (self $user) {
            if ($user->user_code === null) {
                $fy = FinancialYear::label($user->created_at);
                $seq = static::where('fy_label', $fy)->count() + 1;

                $user->forceFill([
                    'fy_label' => $fy,
                    'user_code' => 'MQ/US/'.$fy.'/'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    /** The service contractor this login belongs to, when it is one. */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
