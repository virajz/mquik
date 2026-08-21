<?php

namespace App\Support\Otp;

use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Support\Facades\Hash;

/**
 * Issue and check the one-time codes used for password resets.
 *
 * Delivery is behind `OtpSender`, which is mocked for now — nothing is actually
 * texted. Everything else (hashing, expiry, single use, attempt limits) is real,
 * so swapping in a live SMS gateway is a one-class change.
 */
class OtpService
{
    public function __construct(private OtpSender $sender) {}

    /** Minutes a code stays valid. */
    public const TTL_MINUTES = 10;

    /**
     * Send a fresh code, invalidating any earlier one for the same purpose.
     *
     * @return string|null The plain code when the sender is a mock, so a
     *                     developer can complete the flow; null in production.
     */
    public function issue(User $user, string $purpose = UserOtp::PURPOSE_PASSWORD_RESET): ?string
    {
        // Only one live code at a time — an old SMS arriving late must not work.
        UserOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode();

        UserOtp::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'sent_to' => $user->phone,
        ]);

        return $this->sender->send($user, $code, $purpose);
    }

    /**
     * Check a code and burn it. Returns why it failed, or null on success.
     */
    public function verify(User $user, string $code, string $purpose = UserOtp::PURPOSE_PASSWORD_RESET): ?string
    {
        $otp = UserOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return 'No code has been requested. Ask for a new one.';
        }

        if ($otp->expires_at->isPast()) {
            return 'That code has expired. Ask for a new one.';
        }

        if ($otp->attempts >= UserOtp::MAX_ATTEMPTS) {
            return 'Too many attempts. Ask for a new code.';
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return 'That code is not right.';
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return null;
    }

    /** Six digits, zero-padded — what people expect from an SMS. */
    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
