<?php

use App\Modules\PurchaseEntry\Livewire\Edit;
use App\Modules\PurchaseEntry\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/purchase-entries', Index::class)
        ->middleware('can:purchase_entry.view')
        ->name('purchase-entry.index');

    Route::get('/purchase-entries/create', Edit::class)
        ->middleware('can:purchase_entry.create')
        ->name('purchase-entry.create');

    Route::get('/purchase-entries/{purchaseEntry}/edit', Edit::class)
        ->middleware('can:purchase_entry.update')
        ->name('purchase-entry.edit');
});
