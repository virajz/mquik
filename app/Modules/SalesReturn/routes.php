<?php

use App\Modules\SalesReturn\Livewire\Edit;
use App\Modules\SalesReturn\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/sales-returns', Index::class)
        ->middleware('can:sales_return.view')
        ->name('sales-return.index');

    Route::get('/sales-returns/create', Edit::class)
        ->middleware('can:sales_return.create')
        ->name('sales-return.create');

    Route::get('/sales-returns/{salesReturn}/edit', Edit::class)
        ->middleware('can:sales_return.update')
        ->name('sales-return.edit');
});
