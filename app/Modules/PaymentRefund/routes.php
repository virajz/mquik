<?php

use App\Modules\PaymentRefund\Livewire\Edit;
use App\Modules\PaymentRefund\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payment-refund', Index::class)
        ->middleware('can:payment_refund.view')->name('payment-refund.index');
    Route::get('/payment-refund/create', Edit::class)
        ->middleware('can:payment_refund.create')->name('payment-refund.create');
    Route::get('/payment-refund/{paymentRefund}/edit', Edit::class)
        ->middleware('can:payment_refund.update')->name('payment-refund.edit');
});
