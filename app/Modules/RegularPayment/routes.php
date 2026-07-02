<?php

use App\Modules\RegularPayment\Livewire\Edit;
use App\Modules\RegularPayment\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/regular-payments', Index::class)
        ->middleware('can:regular_payment.view')
        ->name('regular-payment.index');

    Route::get('/regular-payments/create', Edit::class)
        ->middleware('can:regular_payment.create')
        ->name('regular-payment.create');

    Route::get('/regular-payments/{regularPayment}/edit', Edit::class)
        ->middleware('can:regular_payment.update')
        ->name('regular-payment.edit');
});
