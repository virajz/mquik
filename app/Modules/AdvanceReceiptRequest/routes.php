<?php

use App\Modules\AdvanceReceiptRequest\Livewire\Edit;
use App\Modules\AdvanceReceiptRequest\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/advance-receipt-request', Index::class)
        ->middleware('can:advance_receipt_request.view')->name('advance-receipt-request.index');
    Route::get('/advance-receipt-request/create', Edit::class)
        ->middleware('can:advance_receipt_request.create')->name('advance-receipt-request.create');
    Route::get('/advance-receipt-request/{advanceReceiptRequest}/edit', Edit::class)
        ->middleware('can:advance_receipt_request.update')->name('advance-receipt-request.edit');
});
