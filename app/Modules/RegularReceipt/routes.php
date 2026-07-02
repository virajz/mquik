<?php

use App\Modules\RegularReceipt\Livewire\Edit;
use App\Modules\RegularReceipt\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/regular-receipts', Index::class)
        ->middleware('can:regular_receipt.view')
        ->name('regular-receipt.index');

    Route::get('/regular-receipts/create', Edit::class)
        ->middleware('can:regular_receipt.create')
        ->name('regular-receipt.create');

    Route::get('/regular-receipts/{regularReceipt}/edit', Edit::class)
        ->middleware('can:regular_receipt.update')
        ->name('regular-receipt.edit');
});
