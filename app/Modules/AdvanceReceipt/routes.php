<?php

use App\Modules\AdvanceReceipt\Livewire\Edit;
use App\Modules\AdvanceReceipt\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/advance-receipt', Index::class)
        ->middleware('can:advance_receipt.view')->name('advance-receipt.index');
    Route::get('/advance-receipt/create', Edit::class)
        ->middleware('can:advance_receipt.create')->name('advance-receipt.create');
    Route::get('/advance-receipt/{advanceReceipt}/edit', Edit::class)
        ->middleware('can:advance_receipt.update')->name('advance-receipt.edit');
});
