<?php

use App\Modules\SalesEstimate\Livewire\Edit;
use App\Modules\SalesEstimate\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/sales-estimates', Index::class)
        ->middleware('can:sales_estimate.view')
        ->name('sales-estimate.index');

    Route::get('/sales-estimates/create', Edit::class)
        ->middleware('can:sales_estimate.create')
        ->name('sales-estimate.create');

    Route::get('/sales-estimates/{salesEstimate}/edit', Edit::class)
        ->middleware('can:sales_estimate.update')
        ->name('sales-estimate.edit');
});
