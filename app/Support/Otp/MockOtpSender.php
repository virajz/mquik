<?php

namespace App\Support\Otp;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Stand-in for an SMS gateway.
 *
 * Writes the code to the log and hands it back so the UI can show it. Returning
 * the code is the whole reason this class is separate: a real sender returns
 * null, and swapping it in is what stops codes ever being displayed.
 */
class MockOtpSender implements OtpSender
{
    public function send(User $user, string $code, string $purpose): ?string
    {
        Log::info('[MOCK OTP] would text a code', [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'purpose' => $purpose,
            'code' => $code,
        ]);

        return $code;
    }
}
