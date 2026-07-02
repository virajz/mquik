<?php

use App\Modules\ReceiptRefund\Livewire\Edit;
use App\Modules\ReceiptRefund\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/receipt-refunds', Index::class)
        ->middleware('can:receipt_refund.view')
        ->name('receipt-refund.index');

    Route::get('/receipt-refunds/create', Edit::class)
        ->middleware('can:receipt_refund.create')
        ->name('receipt-refund.create');

    Route::get('/receipt-refunds/{receiptRefund}/edit', Edit::class)
        ->middleware('can:receipt_refund.update')
        ->name('receipt-refund.edit');
});
