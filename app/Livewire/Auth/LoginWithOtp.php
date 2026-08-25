<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\UserOtp;
use App\Support\Otp\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Passwordless sign-in: a code sent to whichever identifier the user has —
 * mobile number or email address.
 *
 * Two steps on one screen. Like the password-reset flow, it never reveals
 * whether an identifier is registered: an unknown one gets the same "code sent"
 * screen as a real one, so this cannot be used to discover who has an account.
 */
#[Layout('layouts.auth')]
#[Title('Sign in')]
class LoginWithOtp extends Component
{
    public string $step = 'identifier';   // identifier → code

    /** Phone number or email address — either works. */
    public string $identifier = '';

    public string $code = '';

    public bool $remember = true;

    /** Shown only while delivery is mocked. */
    public ?string $mockCode = null;

    public function sendCode(OtpService $otp): void
    {
        $this->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ], attributes: ['identifier' => 'mobile or email']);

        // Throttle on the identifier itself, so probing is limited whether or
        // not it belongs to anyone.
        $key = 'otp-login:'.mb_strtolower(trim($this->identifier));

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'identifier' => 'Too many requests. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        RateLimiter::hit($key, 600);

        $user = $this->resolveUser();

        // Same outcome either way — an unknown identifier must look identical.
        $this->mockCode = $user ? $otp->issue($user, UserOtp::PURPOSE_LOGIN) : null;
        $this->step = 'code';
    }

    public function verify(OtpService $otp): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $this->resolveUser();

        if (! $user || ! $user->is_active) {
            throw ValidationException::withMessages(['code' => 'That code is not right.']);
        }

        // verify() returns why it failed, or null on success.
        if (($error = $otp->verify($user, $this->code, UserOtp::PURPOSE_LOGIN)) !== null) {
            throw ValidationException::withMessages(['code' => $error]);
        }

        Auth::login($user, $this->remember);
        session()->regenerate();

        $this->redirectIntended('/dashboard');
    }

    /** Digits mean a phone; an @ means an email. */
    protected function resolveUser(): ?User
    {
        $raw = trim($this->identifier);

        if (str_contains($raw, '@')) {
            return User::where('email', mb_strtolower($raw))->first();
        }

        $digits = preg_replace('/\D/', '', $raw);

        return $digits !== '' ? User::where('phone', $digits)->first() : null;
    }

    public function render()
    {
        return view('livewire.auth.login-with-otp');
    }
}
