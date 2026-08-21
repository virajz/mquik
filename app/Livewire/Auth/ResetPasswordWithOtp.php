<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\UserOtp;
use App\Support\Otp\OtpService;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Set a password using a code sent to your phone.
 *
 * Three steps on one screen: give the number, enter the code, choose the
 * password. Deliberately never reveals whether a number is registered — an
 * unknown number gets the same "code sent" screen as a real one, so this cannot
 * be used to discover who has an account.
 */
#[Layout('layouts.auth')]
#[Title('Reset password')]
class ResetPasswordWithOtp extends Component
{
    public string $step = 'phone';   // phone → code → done

    public string $phone = '';

    public string $code = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** Shown only while SMS is mocked. */
    public ?string $mockCode = null;

    public function sendCode(OtpService $otp): void
    {
        $this->validate([
            'phone' => ['required', 'string', 'min:10', 'max:20'],
        ]);

        $digits = preg_replace('/\D/', '', $this->phone);

        // Rate limit on the number, not the account, so probing is throttled
        // whether or not the number exists.
        $key = 'otp-send:'.$digits;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'phone' => 'Too many requests. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        RateLimiter::hit($key, 600);

        $user = User::where('phone', $digits)->first();

        // Same outcome either way — an unknown number must look identical.
        $this->mockCode = $user ? $otp->issue($user) : null;
        $this->step = 'code';
    }

    public function verify(OtpService $otp): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $digits = preg_replace('/\D/', '', $this->phone);
        $user = User::where('phone', $digits)->first();

        if (! $user) {
            throw ValidationException::withMessages(['code' => 'That code is not right.']);
        }

        if ($error = $otp->verify($user, $this->code, UserOtp::PURPOSE_PASSWORD_RESET)) {
            throw ValidationException::withMessages(['code' => $error]);
        }

        $user->forceFill([
            'password' => Hash::make($this->password),
            'must_reset_password' => false,
            'phone_verified_at' => $user->phone_verified_at ?? now(),
        ])->save();

        $this->reset(['code', 'password', 'password_confirmation', 'mockCode']);
        $this->step = 'done';

        Flux::toast(text: 'Password set. You can sign in now.', variant: 'success');
    }

    public function startOver(): void
    {
        $this->reset(['step', 'phone', 'code', 'password', 'password_confirmation', 'mockCode']);
    }

    public function render()
    {
        return view('livewire.auth.reset-password-with-otp');
    }
}
