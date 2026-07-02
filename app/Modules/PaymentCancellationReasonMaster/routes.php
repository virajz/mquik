<?php

use App\Modules\PaymentCancellationReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:payment_cancellation_reason_master.view'])->group(function () {
    Route::get('/payment-cancellation-reason-master', Index::class)->name('payment-cancellation-reason-master.index');
});
