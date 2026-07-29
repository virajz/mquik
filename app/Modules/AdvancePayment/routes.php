<?php

use App\Modules\AdvancePayment\Livewire\Edit;
use App\Modules\AdvancePayment\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/advance-payment', Index::class)
        ->middleware('can:advance_payment.view')->name('advance-payment.index');
    Route::get('/advance-payment/create', Edit::class)
        ->middleware('can:advance_payment.create')->name('advance-payment.create');
    Route::get('/advance-payment/{advancePayment}/edit', Edit::class)
        ->middleware('can:advance_payment.update')->name('advance-payment.edit');
});
