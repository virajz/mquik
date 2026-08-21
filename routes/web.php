<?php

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
