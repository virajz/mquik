<?php

use App\Modules\PaymentHoldReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:payment_hold_reason_master.view'])->group(function () {
    Route::get('/payment-hold-reason-master', Index::class)->name('payment-hold-reason-master.index');
});
