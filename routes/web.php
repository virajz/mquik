<?php

use App\Livewire\Auth\LoginWithOtp;
use App\Livewire\Auth\ResetPasswordWithOtp;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

// Password reset by OTP, alongside Fortify's email-link flow.
Route::livewire('reset-password-otp', ResetPasswordWithOtp::class)
    ->middleware('guest')
    ->name('password.otp');

// Passwordless sign-in: a code to the user's mobile or email.
Route::livewire('login-otp', LoginWithOtp::class)
    ->middleware('guest')
    ->name('login.otp');
