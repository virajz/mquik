<?php

namespace App\Support\Otp;

use App\Models\User;

interface OtpSender
{
    /**
     * Deliver the code.
     *
     * @return string|null The plain code when delivery is mocked (so the flow can
     *                     be completed without an SMS), null once it is real.
     */
    public function send(User $user, string $code, string $purpose): ?string;
}
